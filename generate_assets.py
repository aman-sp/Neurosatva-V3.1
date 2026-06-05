import os
import wave
import struct

def generate_silence_wav(filename, duration=2.0, sample_rate=11025):
    os.makedirs(os.path.dirname(filename), exist_ok=True)
    with wave.open(filename, 'wb') as wav_file:
        # Mono, 1 byte per sample (8-bit unsigned), sample_rate
        wav_file.setnchannels(1)
        wav_file.setsampwidth(1)
        wav_file.setframerate(sample_rate)
        
        num_frames = int(duration * sample_rate)
        # 128 is mid-range (silence) for 8-bit unsigned audio
        data = struct.pack('<' + 'B' * num_frames, *[128] * num_frames)
        wav_file.writeframes(data)

if __name__ == '__main__':
    base_dir = os.path.dirname(os.path.abspath(__file__))
    audio_dir = os.path.join(base_dir, "assets", "audio")
    generate_silence_wav(os.path.join(audio_dir, "focus.mp3"))
    generate_silence_wav(os.path.join(audio_dir, "creative.mp3"))
    generate_silence_wav(os.path.join(audio_dir, "calm.mp3"))
    print("Generated placeholder audio assets successfully.")
