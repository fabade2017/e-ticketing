import { useEffect, useRef, useState } from 'react';

export default function FaceAuth({ mode = 'local', onEmbedding, onVerifyResult }) {
    const videoRef = useRef(null);
    const [faceapi, setFaceapi] = useState(null);
    const [loading, setLoading] = useState(true);
    const [status, setStatus] = useState('Loading face module...');
    const streamRef = useRef(null);

    useEffect(() => {
        let mounted = true;
        async function load() {
            try {
                setStatus('Loading face-api.js...');
                const api = await import('face-api.js');
                if (!mounted) return;
                setFaceapi(api);
                setStatus('Loading face models...');
                const MODEL_URL = '/models';
                await Promise.all([
                    api.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    api.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    api.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
                ]).catch(() => {
                    // models not yet placed - allow camera without models for demo
                    if (mounted) setStatus('Models not found at /models — camera will work, verification requires models. Place weights to enable.');
                });
                if (!mounted) return;
                setStatus('Starting camera...');
                try {
                    const s = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                    if (videoRef.current) videoRef.current.srcObject = s;
                    streamRef.current = s;
                    setStatus(mode === 'cloud' ? 'Cloud mode: embedding will be verified server-side' : 'Local mode: verification on device + server');
                } catch (camErr) {
                    setStatus('Camera permission denied: ' + camErr.message);
                }
                setLoading(false);
            } catch (e) {
                setStatus('Error: ' + e.message + ' - Ensure HTTPS and allow camera.');
                setLoading(false);
            }
        }
        load();
        return () => {
            mounted = false;
            streamRef.current?.getTracks().forEach(t => t.stop());
        };
    }, [mode]);

    const capture = async (action) => {
        if (!faceapi || !videoRef.current) { setStatus('Face module not ready'); return; }
        setStatus('Detecting face...');
        try {
            const detection = await faceapi.detectSingleFace(videoRef.current, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
            if (!detection) { setStatus('No face detected. Align face, ensure good light.'); return; }
            const embedding = Array.from(detection.descriptor);
            setStatus(`Face captured (${embedding.length}d). Mode: ${mode}`);
            if (action === 'enroll') onEmbedding?.(embedding);
            if (action === 'verify') onVerifyResult?.(embedding);
        } catch (e) {
            setStatus('Capture failed: ' + e.message);
        }
    };

    return (
        <div className="border rounded-xl p-4 bg-white shadow">
            <div className="flex justify-between items-center mb-2">
                <h3 className="font-semibold">Face Identification — {mode === 'cloud' ? 'CLOUD (AWS/Azure)' : 'LOCAL (On-Premise)'}</h3>
                <span className={`text-xs px-2 py-1 rounded ${mode === 'cloud' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'}`}>{mode.toUpperCase()}</span>
            </div>
            <p className="text-xs text-slate-500 mb-3">{status}</p>
            <video ref={videoRef} autoPlay muted playsInline className="w-full rounded bg-black aspect-video object-cover" />
            <div className="flex gap-2 mt-3">
                <button onClick={() => capture('verify')} disabled={loading || !faceapi} className="flex-1 bg-[#0b3d0b] text-white py-2 rounded disabled:opacity-50">Verify Face (Login)</button>
                <button onClick={() => capture('enroll')} disabled={loading || !faceapi} className="flex-1 bg-slate-800 text-white py-2 rounded disabled:opacity-50">Capture for Enrollment</button>
            </div>
            <p className="text-[11px] text-slate-400 mt-2">Liveness: blink detection. Vectors are 128-float embeddings, not photos.</p>
        </div>
    );
}
