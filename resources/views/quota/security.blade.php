<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>show-pdf</title>
</head>
<body style="padding:0;margin:0;">
    <embed src="{{ config('filesystems.disks.qcloud.url') . $quotation->pdf }}" type="application/pdf" width="100%" style="height: 100vh;"/>
</body>
</html>
