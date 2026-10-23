(function () {
    'use strict';
    function bindCameraScanner(form, fileInput, options) {
        options = options || {};
        const prefix = options.prefix || 'document';
        const startButton = document.getElementById(prefix + '-scan-start');
        const stopButton = document.getElementById(prefix + '-scan-stop');
        const captureButton = document.getElementById(prefix + '-scan-capture');
        const panel = document.getElementById(prefix + '-scan-panel');
        const video = document.getElementById(prefix + '-scan-video');
        const canvas = document.getElementById(prefix + '-scan-canvas');
        const status = document.getElementById(prefix + '-scan-status');
        if (!form || !fileInput || !startButton || !stopButton || !captureButton || !panel || !video || !canvas) return;
        let stream = null;
        let capturedCount = 0;
        function setStatus(message, isError) {
            if (!status) return;
            status.textContent = message || '';
            status.classList.toggle('text-red-300', !!isError);
            status.classList.toggle('text-green-300', !isError && !!message);
        }
        function stopScan() {
            if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; }
            video.pause(); video.srcObject = null;
            panel.classList.add('hidden'); startButton.classList.remove('hidden');
            stopButton.classList.add('hidden'); captureButton.classList.add('hidden');
        }
        async function startScan() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { setStatus('Camera is not available in this browser or connection.', true); return; }
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
                video.srcObject = stream; await video.play();
                panel.classList.remove('hidden'); startButton.classList.add('hidden');
                stopButton.classList.remove('hidden'); captureButton.classList.remove('hidden');
                setStatus('Camera ready. Capture each page, then submit the form.');
            } catch (error) {
                setStatus('Unable to open the camera. Check browser permission and use HTTPS or localhost.', true);
                if (window.showToast) showToast('Camera permission is required to scan pages.', 'error');
            }
        }
        function capturePage() {
            if (!stream || !video.videoWidth || !video.videoHeight) { setStatus('Wait for the camera preview before capturing.', true); return; }
            canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(blob => {
                if (!blob) { setStatus('The page could not be captured.', true); return; }
                const transfer = new DataTransfer();
                Array.from(fileInput.files || []).forEach(file => transfer.items.add(file));
                capturedCount += 1;
                transfer.items.add(new File([blob], prefix + '-scan-' + Date.now() + '-' + capturedCount + '.jpg', { type: 'image/jpeg' }));
                fileInput.files = transfer.files;
                setStatus(capturedCount + ' scanned page' + (capturedCount === 1 ? '' : 's') + ' ready to upload.');
            }, 'image/jpeg', 0.9);
        }
        startButton.addEventListener('click', startScan); stopButton.addEventListener('click', stopScan); captureButton.addEventListener('click', capturePage);
        form.addEventListener('reset', stopScan);
        if (options.slideover) options.slideover.onClose = stopScan;
    }
    window.bindCameraScanner = bindCameraScanner;
}());
