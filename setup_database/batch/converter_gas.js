let sheetName = 'Main';
let rangeImportFolder = 'B2';
let rangeExportFolder = 'B3';

let exportSettings = [
  { sheetName: '00_データ', exportFileName: 'TC05' },
  { sheetName: '02_問題形式', exportFileName: 'TC02' },
  { sheetName: '03_章・節', exportFileName: 'TC03' },
  { sheetName: '03_章･節', exportFileName: 'TC03' },
  { sheetName: '04_大問', exportFileName: 'TC04' },
  // { sheetName: '05_見出し語', exportFileName: 'TC0?' }, // 08と被ってるけどナニコレ
  { sheetName: '06_レベル', exportFileName: 'TC06' },
  { sheetName: '07_頻度', exportFileName: 'TC07' },
  { sheetName: '08_見出し語', exportFileName: 'TC08' },
  { sheetName: '00_データ', exportFileName: 'other_answer' }
]

// ログ
let startLogRow = 14;
var nextLogRow = 14;

function convert() {
  _initialize();

  _displayMessage("コンバート開始します");

  let as = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = as.getSheetByName(sheetName);

  let importFolderId = sheet.getRange(rangeImportFolder).getValue();
  let exportFolderId = sheet.getRange(rangeExportFolder).getValue();

  let importFolder = DriveApp.getFolderById(importFolderId);
  var srcFolders = importFolder.getFolders();

  while(srcFolders.hasNext()) {
    let srcFolder = srcFolders.next();
    let srcFolderId = srcFolder.getId();

    // エクセルからSpreadsheetに変換
    let convertedFolder = _createSpreadSheet(srcFolderId, exportFolderId);

    var srcFiles = convertedFolder.getFiles();

    while(srcFiles.hasNext()) {
      var srcFile = srcFiles.next();
      
      // 書籍ID取得
      let matches = srcFile.getName().match(/^TC([0-9]+)/);
      _displayMessage(matches[0] + 'の処理を開始します');

      let dstFolder = convertedFolder.createFolder(matches[0]);

      // CSV出力
      _exportCsv(srcFile.getId(), dstFolder);

      // 処理が終わったら削除
      srcFile.setTrashed(true);
      
      _displayMessage(matches[0] + 'の処理を終了しました');
    }
  }

  _displayMessage('すべての処理を終了しました');
}

function _initialize() {
  let as = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = as.getSheetByName(sheetName);

  sheet.getRange('A' + startLogRow + ':B').clear();
  nextLogRow = startLogRow;
}

// CSVを出力する
function _exportCsv(id, dstFolder) {
  let ss = SpreadsheetApp.openById(id);

  for (var i=0; i<exportSettings.length; ++i) {
    var setting = exportSettings[i];
    var result = false;
    
    _displayMessage(setting.exportFileName + ' PROGRESS');
    
    switch (setting.exportFileName) {
      case 'TC02':
        result = _exportTC02(ss, setting.sheetName, setting.exportFileName);
        break;

      case 'TC03':
        result = _exportTC03(ss, setting.sheetName, setting.exportFileName);
        break;

      case 'TC04':
        result = _exportTC04(ss, setting.sheetName, setting.exportFileName);
        break;

      case 'TC05':
        result = _exportTC05(ss, setting.sheetName, setting.exportFileName);
        break;

      case 'TC06':
        result = _exportTC06(ss, setting.sheetName, setting.exportFileName);
        break;

      case 'TC07':
        result = _exportTC07(ss, setting.sheetName, setting.exportFileName);
        break;

      case 'TC08':
        result = _exportTC08(ss, setting.sheetName, setting.exportFileName);
        break;

      case 'other_answer':
        result = _exportOtherAnswer(ss, setting.sheetName, setting.exportFileName);
        break;
      
      default:
        break;
    }

    // CSVを保存
    if (result) {
      _saveCsv(ss, setting.exportFileName, dstFolder);
    }
  }
}

// TC02用データ作成
function _exportTC02(ss, srcName, dstName) {
  var srcSheet = ss.getSheetByName(srcName);

  // ソースがない場合はスルー
  if (!srcSheet) return false;

  var dstSheet = _getOrCreateSheet(ss, dstName);

  // データを一度すべて削除
  dstSheet.getRange('A:C').clear();

  // 見出しデータ追加
  dstSheet.getRange('A1:C1').setValues([['SYUBETUNO','NAME','RATE']])

  // データ開始行と終了行を探す
  var startRow = 2;
  for (var i=2; i<10; ++i) {
    var value = srcSheet.getRange('A'+ i).getValue();

    if (typeof value != 'number') continue;

    startRow = i;
    break;
  }

  var values = srcSheet.getRange('A' + startRow + ':A').getValues();
  var row = values.length;

  var endRow = 0;
  for (var i=0; i<row; ++i) {
    endRow = startRow + i;

    if (typeof values[i][0] == 'number') continue;

    // 数字でなかった場合は1行前までの値が必要な行数になるので-1して終了する
    endRow = startRow + i - 1;
    break;
  }

  // 各行のデータを追加
  srcSheet.getRange('A2:C' + endRow).copyTo(dstSheet.getRange(2, 1), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false);

  return true;
}

// TC03用データ作成
function _exportTC03(ss, srcName, dstName) {
  var srcSheet = ss.getSheetByName(srcName);

  // ソースがない場合はスルー
  if (!srcSheet) return false;

  var dstSheet = _getOrCreateSheet(ss, dstName);

  // データを一度すべて削除
  dstSheet.getRange('A:D').clear();

  // 見出しデータ追加
  dstSheet.getRange('A1:D1').setValues([['CHAPNO','SECNO','CHAPNAME','SECNAME']])

  // 各行のデータを追加
  srcSheet.getRange('A2:A').copyTo(dstSheet.getRange(2, 1), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // CHAPNO
  srcSheet.getRange('B2:B').copyTo(dstSheet.getRange(2, 3), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // SECNO
  srcSheet.getRange('C2:C').copyTo(dstSheet.getRange(2, 2), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // CHAPNAME
  srcSheet.getRange('D2:D').copyTo(dstSheet.getRange(2, 4), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // SECNAME

  return true;
}

// TC04用データ作成
function _exportTC04(ss, srcName, dstName) {
  var srcSheet = ss.getSheetByName(srcName);

  // ソースがない場合はスルー
  if (!srcSheet) return false;

  var dstSheet = _getOrCreateSheet(ss, dstName);

  // データを一度すべて削除
  dstSheet.getRange('A:C').clear();

  // 見出しデータ追加
  dstSheet.getRange('A1:C1').setValues([['DAIMONNO','SORTNO','BUN']])

  // 各行のデータを追加
  srcSheet.getRange('A2:A').copyTo(dstSheet.getRange(2, 1), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // DAIMONNO
  srcSheet.getRange('C2:C').copyTo(dstSheet.getRange(2, 2), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // SORTNO
  srcSheet.getRange('B2:B').copyTo(dstSheet.getRange(2, 3), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // BUN

  return true;
}

// TC05用データ作成
function _exportTC05(ss, srcName, dstName) {
  var srcSheet = ss.getSheetByName(srcName);

  // ソースがない場合はスルー
  if (!srcSheet) return false;

  var dstSheet = _getOrCreateSheet(ss, dstName);

  // データを一度すべて削除
  dstSheet.getRange('A:W').clear();

  // 見出しデータ追加
  dstSheet.getRange('A1:W1').setValues([['SYOMONNO','DAIMONNO','SECNO','SYUBETUNO','SEQNO','MIDASINO','MIDASINAME','REVNO','REVPNO','LEVELNO','FREQENCYNO','BUN','PAGE','ANSLENGTH','ANSNUM','ANSBUN','CHOICES','CHOICESNUM','ANSWERFROM','FILENAME','ANSBUNFULL','COMMENT','SEARCHLABEL']])

  // データ開始行と終了行を探す
  var startRow = 2;
  for (var i=2; i<10; ++i) {
    var value = srcSheet.getRange('A'+ i).getValue();

    if (typeof value != 'number') continue;

    startRow = i;
    break;
  }

  var values = srcSheet.getRange('A' + startRow + ':A').getValues();
  var row = values.length;

  var endRow = 0;
  for (var i=0; i<row; ++i) {
    endRow = startRow + i;

    if (typeof values[i][0] == 'number') continue;

    // 数字でなかった場合は1行前までの値が必要な行数になるので-1して終了する
    endRow = startRow + i - 1;
    break;
  }

  // 各行のデータを追加
  srcSheet.getRange('A' + startRow + ':A' + endRow).copyTo(dstSheet.getRange(2,  1), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // SYOMONNO
  srcSheet.getRange('M' + startRow + ':M' + endRow).copyTo(dstSheet.getRange(2,  2), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // DAIMONNO
  srcSheet.getRange('F' + startRow + ':F' + endRow).copyTo(dstSheet.getRange(2,  3), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // SECNO
  srcSheet.getRange('H' + startRow + ':H' + endRow).copyTo(dstSheet.getRange(2,  4), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // SYUBETUNO
  // srcSheet.getRange('?:?').copyTo(dstSheet.getRange(2,  5), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // SEQNO これだけはデータがないのであとで作る
  srcSheet.getRange('K' + startRow + ':K' + endRow).copyTo(dstSheet.getRange(2,  6), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // MIDASINO
  srcSheet.getRange('L' + startRow + ':L' + endRow).copyTo(dstSheet.getRange(2,  7), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // MIDASINAME
  srcSheet.getRange('J' + startRow + ':J' + endRow).copyTo(dstSheet.getRange(2,  8), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // REVNO
  srcSheet.getRange('Z' + startRow + ':Z' + endRow).copyTo(dstSheet.getRange(2,  9), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // REVPNO
  srcSheet.getRange('O' + startRow + ':O' + endRow).copyTo(dstSheet.getRange(2, 10), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // LEVELNO
  srcSheet.getRange('Q' + startRow + ':Q' + endRow).copyTo(dstSheet.getRange(2, 11), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // FREQENCYNO
  srcSheet.getRange('S' + startRow + ':S' + endRow).copyTo(dstSheet.getRange(2, 12), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // BUN
  srcSheet.getRange('Y' + startRow + ':Y' + endRow).copyTo(dstSheet.getRange(2, 13), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // PAGE
  srcSheet.getRange('W' + startRow + ':W' + endRow).copyTo(dstSheet.getRange(2, 14), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // ANSLENGTH
  srcSheet.getRange('X' + startRow + ':X' + endRow).copyTo(dstSheet.getRange(2, 15), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // ANSNUM
  srcSheet.getRange('V' + startRow + ':V' + endRow).copyTo(dstSheet.getRange(2, 16), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // ANSBUN
  srcSheet.getRange('T' + startRow + ':T' + endRow).copyTo(dstSheet.getRange(2, 17), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // CHOICES
  srcSheet.getRange('AA' + startRow + ':AA' + endRow).copyTo(dstSheet.getRange(2, 18), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // CHOICESNUM
  srcSheet.getRange('AB' + startRow + ':AB' + endRow).copyTo(dstSheet.getRange(2, 19), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // ANSWERFROM
  srcSheet.getRange('AC' + startRow + ':AC' + endRow).copyTo(dstSheet.getRange(2, 20), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // FILENAME
  srcSheet.getRange('AD' + startRow + ':AD' + endRow).copyTo(dstSheet.getRange(2, 21), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // ANSBUNFULL
  srcSheet.getRange('AE' + startRow + ':AE' + endRow).copyTo(dstSheet.getRange(2, 22), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // COMMENT
  srcSheet.getRange('AF' + startRow + ':AF' + endRow).copyTo(dstSheet.getRange(2, 23), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false); // SEARCHLABEL

  // SEQNOだけエクセルにはないので自前で入れる
  // A列の値を取得し、その配列の値を連番に書き換えたものをセットする
  values = srcSheet.getRange('A' + startRow + ':A' + endRow).getValues();
  row = values.length;

  for (var i=0; i<row; ++i) {
    values[i][0] = i+1;
  }

  dstSheet.getRange(2, 5, row, 1).setValues(values);

  // エクセルでは空になっているが、データ的には0にする必要があるカラム対応
  _fillZero( dstSheet.getRange(2,  6, row, 1) ); // MIDASINO
  _fillZero( dstSheet.getRange(2,  8, row, 1) ); // REVNO
  _fillZero( dstSheet.getRange(2,  9, row, 1) ); // REVPNO
  _fillZero( dstSheet.getRange(2, 10, row, 1) ); // LEVELNO
  _fillZero( dstSheet.getRange(2, 11, row, 1) ); // FREQENCYNO
  _fillZero( dstSheet.getRange(2, 18, row, 1) ); // CHOICESNUM

  // 数値ではない値を0にする(●といったゴミデータが入っていることがあったため)
  _invalidDataToZero( dstSheet.getRange(2,  6, row, 1) ); // MIDASINO
  _invalidDataToZero( dstSheet.getRange(2,  8, row, 1) ); // REVNO
  _invalidDataToZero( dstSheet.getRange(2,  9, row, 1) ); // REVPNO
  _invalidDataToZero( dstSheet.getRange(2, 10, row, 1) ); // LEVELNO
  _invalidDataToZero( dstSheet.getRange(2, 11, row, 1) ); // FREQENCYNO
  _invalidDataToZero( dstSheet.getRange(2, 18, row, 1) ); // CHOICESNUM

  return true;
}

// 0で埋める
function _fillZero(range) {
  var values = range.getValues();

  for (var y=0; y<values.length; ++y) {
    for (var x=0; x<values[y].length; ++x) {
      if (values[y][x] == "") values[y][x] = 0;
    }
  }
  
  range.setValues(values);
}

// 数値ではない値を0にする
function _invalidDataToZero(range) {
  var values = range.getValues();

  for (var y=0; y<values.length; ++y) {
    for (var x=0; x<values[y].length; ++x) {
      // if (values[y][x] == "") values[y][x] = 0;
      var value = values[y][x];
      if (typeof value != 'string') continue;

      // 数字のみじゃない場合
      if (!value.match(/^[0-9,\-\.]+$/)) {
        _displayMessage('(' + y +',' + x + ') にある ' + value + " を 0 に変換しました。");
        values[y][x] = 0;
      }
    }
  }
  
  range.setValues(values);
}

// 別解データ作成
function _exportOtherAnswer(ss, srcName, dstName) {
  var srcSheet = ss.getSheetByName(srcName);

  // ソースがない場合はスルー
  if (!srcSheet) return false;

  var dstSheet = _getOrCreateSheet(ss, dstName);

  // データを一度すべて削除
  dstSheet.getRange('A:B').clear();

  // 見出しデータ追加
  dstSheet.getRange('A1:B1').setValues([['syomon_no','answer']])

  // データ開始行と終了行を探す
  var startRow = 2;
  for (var i=2; i<10; ++i) {
    var value = srcSheet.getRange('A'+ i).getValue();

    if (typeof value != 'number') continue;

    startRow = i;
    break;
  }

  var values = srcSheet.getRange('A' + startRow + ':A').getValues();
  var row = values.length;

  var endRow = 0;
  for (var i=0; i<row; ++i) {
    endRow = startRow + i;

    if (typeof values[i][0] == 'number') continue;

    // 数字でなかった場合は1行前までの値が必要な行数になるので-1して終了する
    endRow = startRow + i - 1;
    break;
  }

  values = srcSheet.getRange('AG' + startRow + ':AG' + endRow).getValues();

  // AG列は33なのでその右隣から
  let baseCol = 34;
  var dstRow = 2;
  var result = false;
  var queue = [];

  for (var i=0; i<values.length; ++i) {
    if (typeof values[i][0] != 'number') continue;

    var currentRow = startRow + i;
    var num = values[i][0];
    var shomonNo = srcSheet.getRange(currentRow, 1).getValue();
    
    // 答えごとに別のレコードにする
    for (var colIterator=0; colIterator<num; ++colIterator) {
      var currentValue = srcSheet.getRange(currentRow, baseCol + colIterator).getValue();

      // 前後の空白を除去する
      if (typeof currentValue == 'string') currentValue = currentValue.trim();

      queue.push([shomonNo, currentValue]);
      
      // dstSheet.getRange(dstRow, 1, 1, 2).setValues([[shomonNo, currentValue]]);
      // ++dstRow;
    }

    if (queue.length >= 100) {
      dstSheet.getRange(dstRow, 1, queue.length, 2).setValues(queue);
      dstRow += queue.length;

      queue = [];
    }

    result = true;
  }

  if (queue.length > 0) {
    dstSheet.getRange(dstRow, 1, queue.length, 2).setValues(queue);
    dstRow += queue.length;

    queue = [];
  }
  
  return result;
}

// TC06用データ作成
function _exportTC06(ss, srcName, dstName) {
  var srcSheet = ss.getSheetByName(srcName);

  // ソースがない場合はスルー
  if (!srcSheet) return false;

  // 値がない場合はスルー
  var firstValue = srcSheet.getRange('A2:B2').getValue();
  if (firstValue == '') return false;

  var dstSheet = _getOrCreateSheet(ss, dstName);

  // データを一度すべて削除
  dstSheet.getRange('A:B').clear();

  // 見出しデータ追加
  dstSheet.getRange('A1:B1').setValues([['LEVELNO','NAME']])

  // 各行のデータを追加
  // TC06は列の順が同じなのでそのままコピーする
  srcSheet.getRange('A2:B').copyTo(dstSheet.getRange(2, 1), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false);

  return true;
}

// TC07用データ作成
function _exportTC07(ss, srcName, dstName) {
  var srcSheet = ss.getSheetByName(srcName);

  // ソースがない場合はスルー
  if (!srcSheet) return false;

  // 値がない場合はスルー
  var firstValue = srcSheet.getRange('A2:B2').getValue();
  if (firstValue == '') return false;

  var dstSheet = _getOrCreateSheet(ss, dstName);

  // データを一度すべて削除
  dstSheet.getRange('A:B').clear();

  // 見出しデータ追加
  dstSheet.getRange('A1:B1').setValues([['FREQUENCYNO','NAME']])

  // 各行のデータを追加
  // TC06は列の順が同じなのでそのままコピーする
  srcSheet.getRange('A2:B').copyTo(dstSheet.getRange(2, 1), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false);

  return true;
}

// TC08用データ作成
function _exportTC08(ss, srcName, dstName) {
  var srcSheet = ss.getSheetByName(srcName);

  // ソースがない場合はスルー
  if (!srcSheet) return false;

  // 値がない場合はスルー
  var firstValue = srcSheet.getRange('A2:B2').getValue();
  if (firstValue == '') return false;

  var dstSheet = _getOrCreateSheet(ss, dstName);

  // データを一度すべて削除
  dstSheet.getRange('A:B').clear();

  // 見出しデータ追加
  dstSheet.getRange('A1:B1').setValues([['MIDASINO','NAME']])

  // 各行のデータを追加
  // TC06は列の順が同じなのでそのままコピーする
  srcSheet.getRange('A2:B').copyTo(dstSheet.getRange(2, 1), SpreadsheetApp.CopyPasteType.PASTE_VALUES, false);

  return true;
}

// CSVファイルに保存する
// https://moripro.net/gas-sheet-to-csv/
// https://qiita.com/q_masa/items/c7658305344f7977582e
function _saveCsv(ss, sheetName, dstFolder) {
  var sheet = ss.getSheetByName(sheetName);
  
  //データ範囲を二次元配列で取得
  var values = sheet.getDataRange().getValues();

  // カンマを含むデータは "" で括る
  for (var y=0; y<values.length; ++y) {
    for (var x=0; x<values[y].length; ++x) {
      var value = values[y][x];

      if (typeof value != 'string') continue;

      // 改行コードは削除する
      if (value.match(/[\r\n]+/)) {
        value = value.replace(/[\r\n]+/g, '');
        values[y][x] = value;
      }

      var isQuotation = false;

      // "を含んでいる場合は"" にエスケープし、後で全体を""で括る
      if (value.match(/"/)) {
        value = value.replace(/"/g, '""');
        isQuotation = true;
      }

      // カンマを含んでいる場合は後で全体を""で括る
      if (value.match(/,/)) {
        isQuotation = true;
      }

      // ""で括る
      if (isQuotation) {
        value = '"' + value + '"';
      }
      values[y][x] = value;
    }
  }
  
  // 二次元配列をカンマ区切りの文字列に変換し、最後に改行を追加する
  var csv = values.join('\n') + "\n";
  
  // Blobオブジェクトの作成
  var blob = Utilities.newBlob(csv, MimeType.CSV, sheetName + '.csv');

  // CSVファイルを作成
  dstFolder.createFile(blob);
}

// シートを取得または作成する
function _getOrCreateSheet(ss, sheetName) {

  var sheet = ss.getSheetByName(sheetName);

  if (!sheet) {
    var num = ss.getNumSheets();
    sheet = ss.insertSheet(sheetName, num);
  }

  return sheet;
}

// エクセルをSpreadsheetに変換する
function _createSpreadSheet(targetFolderId, exportFolderId) {
  var srcFolder = DriveApp.getFolderById(targetFolderId);
  let folderName = srcFolder.getName();

  // エクスポートフォルダ内に出力先フォルダを新たに作る
  let dstFolder = DriveApp.createFolder(folderName);
  var exportFolder = DriveApp.getFolderById(exportFolderId);
  dstFolder.moveTo(exportFolder);

  // エクセルファイルをspreadsheetにコンバート
  var srcFiles = srcFolder.getFiles();

  while(srcFiles.hasNext()) {
    var srcFile = srcFiles.next();
    _convertExcelToGsheet(dstFolder.getId(), srcFile)
  }

  return dstFolder;
}

// エクセルファイルをSpreadsheetにコンバートする
// https://teratail.com/questions/239962
function _convertExcelToGsheet(folderId, excelFileObj){
  var convertInfo = {
    title: excelFileObj.getName(),
    mimeType: MimeType.GOOGLE_SHEETS,
    parents: [{id: folderId}],
  };
  var res = Drive.Files.insert(convertInfo, excelFileObj.getBlob());

  _displayMessage(excelFileObj.getName() + " をSpreadsheetに変換しました");

  return res.id;
}

function _displayMessage(message) {
  _outputLog(message);
  Logger.log(message);
  SpreadsheetApp.getActiveSpreadsheet().toast(message);
}

function _outputLog(log) {
  let as = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = as.getSheetByName(sheetName);

  const date = new Date();
  let time = date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds();

  sheet.getRange('A' + nextLogRow + ':B' + nextLogRow).setValues([[time, log]]);
  
  nextLogRow = nextLogRow + 1;

  SpreadsheetApp.flush();
}