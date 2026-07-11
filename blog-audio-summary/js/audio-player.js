/**
 * blog Audio Player - ResponsiveVoice FREE
 * UK English Feminine Voice - No API costs!
 */

(function() {
    'use strict';
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPlayer);
    } else {
        initPlayer();
    }
    
    function initPlayer() {
        const playerContainer = document.querySelector('.blog-audio-player');
        if (!playerContainer) return;
        
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playIcon = document.querySelector('.play-icon');
        const pauseIcon = document.querySelector('.pause-icon');
        const progressFill = document.getElementById('progressFill');
        const progressBar = document.querySelector('.progress-bar');
        const currentTimeEl = document.getElementById('currentTime');
        const totalTimeEl = document.getElementById('totalTime');
        const speedControl = document.getElementById('speedControl');
        const audioError = document.getElementById('audioError');
        
        const articleText = playerContainer.dataset.articleText;
        let isPlaying = false;
        let startTime = 0;
        let currentPosition = 0;
        let totalDuration = 0;
        let progressInterval = null;
        
        // Wait for ResponsiveVoice to load
        waitForResponsiveVoice();
        
        function waitForResponsiveVoice() {
            if (typeof responsiveVoice !== 'undefined') {
                init();
            } else {
                setTimeout(waitForResponsiveVoice, 100);
            }
        }
        
        function init() {
            // Estimate duration
            const words = articleText.split(/\s+/).length;
            totalDuration = (words / 150) * 60; // 150 words per minute
            totalTimeEl.textContent = formatTime(totalDuration);
            
            // Set default voice settings from WordPress
            const voice = typeof blogAudioConfig !== 'undefined' 
                ? blogAudioConfig.voice 
                : 'UK English Female';
            
            // Event listeners
            playPauseBtn.addEventListener('click', togglePlayPause);
            speedControl.addEventListener('change', handleSpeedChange);
            
            // ResponsiveVoice callbacks
            responsiveVoice.OnVoiceReady = function() {
                console.log('ResponsiveVoice ready');
            };
            
            // Keyboard shortcuts
            document.addEventListener('keydown', handleKeyboard);
        }
        
        function togglePlayPause() {
            if (isPlaying) {
                pause();
            } else {
                play();
            }
        }
        
        function play() {
            if (responsiveVoice.isPlaying()) {
                responsiveVoice.resume();
            } else {
                const voice = typeof blogAudioConfig !== 'undefined' 
                    ? blogAudioConfig.voice 
                    : 'UK English Female';
                
                const rate = parseFloat(speedControl.value);
                const pitch = typeof blogAudioConfig !== 'undefined' 
                    ? parseFloat(blogAudioConfig.pitch) 
                    : 1;
                
                responsiveVoice.speak(articleText, voice, {
                    rate: rate,
                    pitch: pitch,
                    volume: 1,
                    onstart: handleStart,
                    onend: handleEnd,
                    onerror: handleError
                });
            }
            
            isPlaying = true;
            updatePlayPauseButton(true);
            startProgressUpdates();
        }
        
        function pause() {
            responsiveVoice.pause();
            isPlaying = false;
            updatePlayPauseButton(false);
            stopProgressUpdates();
        }
        
        function stop() {
            responsiveVoice.cancel();
            isPlaying = false;
            updatePlayPauseButton(false);
            stopProgressUpdates();
            currentPosition = 0;
            progressFill.style.width = '0%';
            currentTimeEl.textContent = '0:00';
        }
        
        function updatePlayPauseButton(playing) {
            if (playing) {
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
                playPauseBtn.setAttribute('aria-label', 'Pause');
            } else {
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
                playPauseBtn.setAttribute('aria-label', 'Play');
            }
        }
        
        function handleStart() {
            startTime = Date.now();
            console.log('ResponsiveVoice: Playback started');
        }
        
        function handleEnd() {
            isPlaying = false;
            updatePlayPauseButton(false);
            stopProgressUpdates();
            progressFill.style.width = '100%';
            currentTimeEl.textContent = formatTime(totalDuration);
            console.log('ResponsiveVoice: Playback ended');
        }
        
        function handleError(error) {
            console.error('ResponsiveVoice error:', error);
            showError('Audio playback error. Please try again.');
            isPlaying = false;
            updatePlayPauseButton(false);
            stopProgressUpdates();
        }
        
        function handleSpeedChange() {
            const wasPlaying = isPlaying;
            
            if (wasPlaying) {
                responsiveVoice.cancel();
                
                // Recalculate duration
                const words = articleText.split(/\s+/).length;
                const speed = parseFloat(speedControl.value);
                totalDuration = (words / (150 * speed)) * 60;
                totalTimeEl.textContent = formatTime(totalDuration);
                
                // Restart playback
                play();
            }
        }
        
        function startProgressUpdates() {
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            progressInterval = setInterval(updateProgress, 100);
        }
        
        function stopProgressUpdates() {
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
        }
        
        function updateProgress() {
            if (!isPlaying) return;
            
            const elapsed = (Date.now() - startTime) / 1000;
            const progress = Math.min((elapsed / totalDuration) * 100, 100);
            
            progressFill.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', Math.round(progress));
            currentTimeEl.textContent = formatTime(elapsed);
        }
        
        function handleKeyboard(e) {
            if (!playerContainer.contains(document.activeElement)) return;
            
            switch(e.key) {
                case ' ':
                case 'k':
                    e.preventDefault();
                    togglePlayPause();
                    break;
                case 'Escape':
                    e.preventDefault();
                    stop();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    increaseSpeed();
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    decreaseSpeed();
                    break;
            }
        }
        
        function increaseSpeed() {
            const currentSpeed = parseFloat(speedControl.value);
            const speeds = [0.75, 1, 1.25, 1.5];
            const currentIndex = speeds.indexOf(currentSpeed);
            if (currentIndex < speeds.length - 1) {
                speedControl.value = speeds[currentIndex + 1];
                handleSpeedChange();
            }
        }
        
        function decreaseSpeed() {
            const currentSpeed = parseFloat(speedControl.value);
            const speeds = [0.75, 1, 1.25, 1.5];
            const currentIndex = speeds.indexOf(currentSpeed);
            if (currentIndex > 0) {
                speedControl.value = speeds[currentIndex - 1];
                handleSpeedChange();
            }
        }
        
        function formatTime(seconds) {
            if (!isFinite(seconds) || seconds < 0) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        }
        
        function showError(message) {
            audioError.textContent = message;
            audioError.style.display = 'block';
            setTimeout(() => {
                audioError.style.display = 'none';
            }, 5000);
        }
        
        // Cleanup
        window.addEventListener('beforeunload', () => {
            if (isPlaying) {
                responsiveVoice.cancel();
            }
        });
        
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && isPlaying) {
                // Optionally pause when tab hidden
                // pause();
            }
        });
    }
})();
