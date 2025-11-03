#!/bin/sh

## AMI 作成
# ベースとなるインスタンス spapp-prod-ec2-develop のインスタンスID
INSTANCE_ID=i-03932f9d1dfc65886

DATE=`date +'%Y%m%d%H%M%S'`
AMI_NAME=spapp-prod-ami-$DATE

AMI_ID=$(aws ec2 create-image --instance-id ${INSTANCE_ID} --name ${AMI_NAME} --reboot --tag-specifications \
    "ResourceType=image,Tags=[{Key=Project,Value=SpApp},{Key=Environment,Value=Prod},{Key=Name,Value=${AMI_NAME}}]" \
    "ResourceType=snapshot,Tags=[{Key=Project,Value=SpApp},{Key=Environment,Value=Prod},{Key=Name,Value=${AMI_NAME}}]" | \
    jq -r '.ImageId' \
    )

echo "AMI_ID=$AMI_ID"

# AMI 作成完了まで待機
aws ec2 wait image-available \
    --image-ids $AMI_ID

echo "AMI image available"


## 起動テンプレート更新
TEMPLATE_NAME=spapp-prod-launch-template-202303


TEMPLATE_ID=$( aws ec2 describe-launch-templates --launch-template-names ${TEMPLATE_NAME} | \
    jq -r '.LaunchTemplates[].LaunchTemplateId' \
    )

echo "TEMPLATE_ID: ${TEMPLATE_ID}"

PREV_TEMPLATE_VER=$( aws ec2 describe-launch-templates --launch-template-id ${TEMPLATE_ID} | \
    jq -r '.LaunchTemplates[].DefaultVersionNumber' \
    )

echo "PREV_TEMPLATE_VER: ${PREV_TEMPLATE_VER}"

# AMIを指定して起動テンプレートの新しいバージョンを作成する
NEW_TEMPLATE_JSON=$(aws ec2 create-launch-template-version \
    --launch-template-id ${TEMPLATE_ID} \
    --source-version ${PREV_TEMPLATE_VER} \
    --launch-template-data '{"ImageId":"'${AMI_ID}'"}')

# 新しいバージョン番号を取得
NEW_TEMPLATE_VER=$(echo ${NEW_TEMPLATE_JSON} | jq -r '.LaunchTemplateVersion.VersionNumber')

echo "NEW_TEMPLATE_VER: ${NEW_TEMPLATE_VER}"

# aws ec2 describe-launch-template-versions \
#     --launch-template-id ${TEMPLATE_ID} \
#     --versions ${NEW_TEMPLATE_VER} \
#     > launch-template-after.json

NEW_DEFAULT_TEMPLATE_VER=$( aws ec2 modify-launch-template \
    --launch-template-id ${TEMPLATE_ID} \
    --default-version ${NEW_TEMPLATE_VER} | \
    jq -r '.LaunchTemplate.DefaultVersionNumber' \
    )

echo "NEW_DEFAULT_TEMPLATE_VER: ${NEW_DEFAULT_TEMPLATE_VER}"

echo "DONE"