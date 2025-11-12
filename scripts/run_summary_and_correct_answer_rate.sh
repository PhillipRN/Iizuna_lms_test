#!/bin/bash
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
REPO_ROOT=$(cd "${SCRIPT_DIR}/.." && pwd)
LOG_DIR="${REPO_ROOT}/app/Logs"
LOG_FILE="${LOG_DIR}/summary_and_correct_answer_rate.log"

mkdir -p "${LOG_DIR}"
cd "${REPO_ROOT}"

timestamp=$(date '+%Y-%m-%dT%H:%M:%S%z')
echo "[${timestamp}] summary_and_correct_answer_rate start" >> "${LOG_FILE}"
php app/Commands/summary_and_correct_answer_rate.php >> "${LOG_FILE}" 2>&1
