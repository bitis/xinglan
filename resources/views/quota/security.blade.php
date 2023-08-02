<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>防伪码核验</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.worker.min.js"></script>
</head>
<body>
<canvas id="the-canvas" style="border: 1px solid black; direction: ltr;"></canvas>
<script id="script">
    /* pdf url */
    const url = '{{ config('filesystems.disks.qcloud.url') . $quotation->pdf }}';
    const loadingTask = pdfjsLib.getDocument(url);
    (async () => {
        const pdf = await loadingTask.promise;
        const page = await pdf.getPage(1);
        const scale = 1.2;
        const viewport = page.getViewport({scale});
        const outputScale = window.devicePixelRatio || 1;
        const canvas = document.getElementById("the-canvas");
        const context = canvas.getContext("2d");
        canvas.width = Math.floor(viewport.width * outputScale);
        canvas.height = Math.floor(viewport.height * outputScale);
        canvas.style.width = Math.floor(viewport.width) + "px";
        canvas.style.height = Math.floor(viewport.height) + "px";
        const transform = outputScale !== 1
            ? [outputScale, 0, 0, outputScale, 0, 0]
            : null;
        const renderContext = {
            canvasContext: context,
            transform,
            viewport,
        };
        page.render(renderContext);
    })();
</script>
</body>
</html>
