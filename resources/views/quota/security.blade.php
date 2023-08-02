<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>防伪码核验</title>
</head>
<body style="padding:0;margin:0;">
    <iframe
        style="
          width:100vw;
          min-height: 100vh;
          border: none;
          overflow: hidden;
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          z-index: 9999;
        "
        src="{{ config('filesystems.disks.qcloud.url') . $quotation->pdf }}"
        frameborder="0"
      ></iframe>
</body>
</html>
