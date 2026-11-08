#!/usr/bin/env python3
"""
EchoTrack Audio Watermarking Tool

Embeds an ultrasonic beacon signal into existing audio files.
The watermark is inaudible (18-22kHz) but can be detected by smartphones.

Usage:
    python watermark_audio.py input.mp3 BEACON_ID output.mp3
    python watermark_audio.py input.wav "MY_BEACON" output.wav --strength 0.3
"""

import argparse
import os
import sys
import numpy as np
from scipy.io import wavfile
from scipy import signal as scipy_signal
import subprocess
import tempfile


# FSK Parameters (must match tone_generator.py)
SAMPLE_RATE = 48000
FREQ_LOW = 18500
FREQ_HIGH = 19500
SYMBOL_DURATION = 0.05
PREAMBLE_FREQ = 19000
PREAMBLE_DURATION = 0.2


def string_to_bits(s: str) -> list:
    """Convert a string to a list of bits."""
    bits = []
    for char in s:
        byte = ord(char)
        for i in range(7, -1, -1):
            bits.append((byte >> i) & 1)
    return bits


def generate_fsk_signal(bits: list, sample_rate: int = SAMPLE_RATE) -> np.ndarray:
    """Generate FSK-modulated audio signal from bits."""
    samples_per_symbol = int(sample_rate * SYMBOL_DURATION)
    total_samples = len(bits) * samples_per_symbol
    
    t = np.arange(total_samples) / sample_rate
    frequencies = np.zeros(total_samples)
    
    for i, bit in enumerate(bits):
        start = i * samples_per_symbol
        end = (i + 1) * samples_per_symbol
        frequencies[start:end] = FREQ_HIGH if bit else FREQ_LOW
    
    phase = np.cumsum(2 * np.pi * frequencies / sample_rate)
    return np.sin(phase)


def generate_preamble(sample_rate: int = SAMPLE_RATE) -> np.ndarray:
    """Generate preamble/sync signal."""
    samples = int(sample_rate * PREAMBLE_DURATION)
    t = np.arange(samples) / sample_rate
    preamble = np.sin(2 * np.pi * PREAMBLE_FREQ * t)
    
    # Smooth envelope
    envelope = np.ones(samples)
    ramp_len = int(0.01 * sample_rate)
    envelope[:ramp_len] = np.linspace(0, 1, ramp_len)
    envelope[-ramp_len:] = np.linspace(1, 0, ramp_len)
    
    return preamble * envelope


def generate_beacon_signal(beacon_id: str, duration: float, 
                           sample_rate: int = SAMPLE_RATE) -> np.ndarray:
    """Generate complete beacon signal to overlay."""
    # Encode beacon ID
    beacon_bits = string_to_bits(beacon_id)
    length_bits = [(len(beacon_id) >> i) & 1 for i in range(7, -1, -1)]
    
    checksum = 0
    for char in beacon_id:
        checksum ^= ord(char)
    checksum_bits = [(checksum >> i) & 1 for i in range(7, -1, -1)]
    
    all_bits = length_bits + beacon_bits + checksum_bits
    
    # Generate one beacon packet
    preamble = generate_preamble(sample_rate)
    data = generate_fsk_signal(all_bits, sample_rate)
    packet = np.concatenate([preamble, data])
    
    # Add gap between packets
    gap = np.zeros(int(0.5 * sample_rate))  # 0.5 second gap
    packet_with_gap = np.concatenate([packet, gap])
    
    # Repeat to fill duration
    total_samples = int(duration * sample_rate)
    repeats = int(np.ceil(total_samples / len(packet_with_gap)))
    full_signal = np.tile(packet_with_gap, repeats)[:total_samples]
    
    return full_signal


def read_audio_file(filepath: str) -> tuple:
    """
    Read audio file and return (sample_rate, audio_data).
    Converts to WAV if needed using ffmpeg.
    """
    ext = os.path.splitext(filepath)[1].lower()
    
    if ext == '.wav':
        sample_rate, audio_data = wavfile.read(filepath)
        return sample_rate, audio_data
    
    # Convert to WAV using ffmpeg
    with tempfile.NamedTemporaryFile(suffix='.wav', delete=False) as tmp:
        tmp_path = tmp.name
    
    try:
        subprocess.run([
            'ffmpeg', '-y', '-i', filepath,
            '-ar', str(SAMPLE_RATE),
            '-ac', '1',  # Mono for simplicity
            tmp_path
        ], check=True, capture_output=True)
        
        sample_rate, audio_data = wavfile.read(tmp_path)
        return sample_rate, audio_data
    finally:
        if os.path.exists(tmp_path):
            os.unlink(tmp_path)


def save_audio_file(filepath: str, sample_rate: int, audio_data: np.ndarray) -> None:
    """
    Save audio data to file.
    Converts from WAV if output is MP3 using ffmpeg.
    """
    ext = os.path.splitext(filepath)[1].lower()
    
    if ext == '.wav':
        # Ensure 16-bit integer format
        if audio_data.dtype == np.float64 or audio_data.dtype == np.float32:
            audio_data = (audio_data * 32767).astype(np.int16)
        wavfile.write(filepath, sample_rate, audio_data)
        return
    
    # Save as WAV first, then convert
    with tempfile.NamedTemporaryFile(suffix='.wav', delete=False) as tmp:
        tmp_path = tmp.name
    
    try:
        if audio_data.dtype == np.float64 or audio_data.dtype == np.float32:
            audio_data = (audio_data * 32767).astype(np.int16)
        wavfile.write(tmp_path, sample_rate, audio_data)
        
        subprocess.run([
            'ffmpeg', '-y', '-i', tmp_path,
            '-b:a', '320k',  # High bitrate to preserve ultrasonics
            filepath
        ], check=True, capture_output=True)
    finally:
        if os.path.exists(tmp_path):
            os.unlink(tmp_path)


def watermark_audio(input_path: str, beacon_id: str, output_path: str,
                    strength: float = 0.15) -> dict:
    """
    Embed ultrasonic beacon signal into audio file.
    
    Args:
        input_path: Path to input audio file
        beacon_id: Beacon ID to encode
        output_path: Path for watermarked output
        strength: Volume of watermark (0.0-1.0), default 0.15
        
    Returns:
        dict with metadata about the watermarking
    """
    print(f"Reading: {input_path}")
    sample_rate, audio_data = read_audio_file(input_path)
    
    # Convert to float for processing
    if audio_data.dtype == np.int16:
        audio_float = audio_data.astype(np.float64) / 32767.0
    elif audio_data.dtype == np.int32:
        audio_float = audio_data.astype(np.float64) / 2147483647.0
    else:
        audio_float = audio_data.astype(np.float64)
    
    # Handle stereo
    is_stereo = len(audio_float.shape) > 1
    if is_stereo:
        # Mix to mono for beacon, then add to both channels
        mono = np.mean(audio_float, axis=1)
        duration = len(mono) / sample_rate
    else:
        duration = len(audio_float) / sample_rate
    
    print(f"Duration: {duration:.2f}s, Sample rate: {sample_rate}Hz")
    
    # Resample beacon signal to match audio sample rate
    beacon_signal = generate_beacon_signal(beacon_id, duration, SAMPLE_RATE)
    
    if sample_rate != SAMPLE_RATE:
        # Resample beacon signal
        num_samples = int(duration * sample_rate)
        beacon_signal = scipy_signal.resample(beacon_signal, num_samples)
    
    # Normalize beacon signal and apply strength
    beacon_signal = beacon_signal / np.max(np.abs(beacon_signal)) * strength
    
    # Add beacon to audio
    if is_stereo:
        watermarked = audio_float.copy()
        watermarked[:, 0] += beacon_signal
        watermarked[:, 1] += beacon_signal
    else:
        watermarked = audio_float + beacon_signal
    
    # Clip to prevent distortion
    watermarked = np.clip(watermarked, -1.0, 1.0)
    
    # Save output
    print(f"Writing: {output_path}")
    save_audio_file(output_path, sample_rate, watermarked)
    
    return {
        'beacon_id': beacon_id,
        'duration': duration,
        'sample_rate': sample_rate,
        'strength': strength,
        'frequencies': f"{FREQ_LOW}-{FREQ_HIGH} Hz"
    }


def main():
    parser = argparse.ArgumentParser(
        description='Watermark audio files with ultrasonic beacon signals'
    )
    parser.add_argument(
        'input',
        help='Input audio file (MP3, WAV)'
    )
    parser.add_argument(
        'beacon_id',
        help='Beacon ID to encode'
    )
    parser.add_argument(
        'output',
        help='Output audio file'
    )
    parser.add_argument(
        '--strength', '-s',
        type=float,
        default=0.15,
        help='Watermark strength 0.0-1.0 (default: 0.15)'
    )
    
    args = parser.parse_args()
    
    if not os.path.exists(args.input):
        print(f"Error: Input file not found: {args.input}", file=sys.stderr)
        sys.exit(1)
    
    try:
        result = watermark_audio(
            args.input,
            args.beacon_id,
            args.output,
            args.strength
        )
        
        print(f"\n✓ Watermarked successfully!")
        print(f"  Beacon ID:  {result['beacon_id']}")
        print(f"  Duration:   {result['duration']:.2f}s")
        print(f"  Frequency:  {result['frequencies']}")
        print(f"  Strength:   {result['strength']}")
        
    except subprocess.CalledProcessError as e:
        print(f"Error: ffmpeg failed - {e.stderr.decode()}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
