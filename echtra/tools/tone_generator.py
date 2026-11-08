#!/usr/bin/env python3
"""
EchoTrack Ultrasonic Tone Generator

Generates 18-20kHz ultrasonic tones with FSK-encoded beacon IDs.
These tones are inaudible to humans but can be detected by smartphones.

Usage:
    python tone_generator.py --beacon-id "ABC123" --output tone.wav
    python tone_generator.py --beacon-id "ABC123" --output tone.wav --duration 5
"""

import argparse
import numpy as np
from scipy.io import wavfile
from scipy import signal
import struct


# FSK Parameters
SAMPLE_RATE = 48000  # Hz - standard audio sample rate
FREQ_LOW = 18500     # Hz - represents binary 0
FREQ_HIGH = 19500    # Hz - represents binary 1
SYMBOL_DURATION = 0.05  # seconds per bit
PREAMBLE_FREQ = 19000   # Hz - sync signal
PREAMBLE_DURATION = 0.2 # seconds


def string_to_bits(s: str) -> list:
    """Convert a string to a list of bits."""
    bits = []
    for char in s:
        byte = ord(char)
        for i in range(7, -1, -1):  # MSB first
            bits.append((byte >> i) & 1)
    return bits


def bits_to_string(bits: list) -> str:
    """Convert a list of bits back to a string."""
    chars = []
    for i in range(0, len(bits), 8):
        byte = 0
        for j in range(8):
            if i + j < len(bits):
                byte = (byte << 1) | bits[i + j]
        chars.append(chr(byte))
    return ''.join(chars)


def generate_fsk_signal(bits: list, sample_rate: int = SAMPLE_RATE) -> np.ndarray:
    """
    Generate FSK-modulated audio signal from bits.
    
    Uses Frequency Shift Keying (FSK):
    - Binary 0 = 18.5 kHz tone
    - Binary 1 = 19.5 kHz tone
    """
    samples_per_symbol = int(sample_rate * SYMBOL_DURATION)
    total_samples = len(bits) * samples_per_symbol
    
    # Create time array
    t = np.arange(total_samples) / sample_rate
    
    # Generate frequency array
    frequencies = np.zeros(total_samples)
    for i, bit in enumerate(bits):
        start = i * samples_per_symbol
        end = (i + 1) * samples_per_symbol
        frequencies[start:end] = FREQ_HIGH if bit else FREQ_LOW
    
    # Generate signal with smooth phase transitions
    phase = np.cumsum(2 * np.pi * frequencies / sample_rate)
    signal_data = np.sin(phase)
    
    return signal_data


def generate_preamble(sample_rate: int = SAMPLE_RATE) -> np.ndarray:
    """Generate a preamble/sync signal to help the receiver lock on."""
    samples = int(sample_rate * PREAMBLE_DURATION)
    t = np.arange(samples) / sample_rate
    
    # Create a distinctive preamble: alternating high-low-high-low
    preamble = np.sin(2 * np.pi * PREAMBLE_FREQ * t)
    
    # Add amplitude envelope to reduce clicking
    envelope = np.ones(samples)
    ramp_len = int(0.01 * sample_rate)  # 10ms ramp
    envelope[:ramp_len] = np.linspace(0, 1, ramp_len)
    envelope[-ramp_len:] = np.linspace(1, 0, ramp_len)
    
    return preamble * envelope


def generate_beacon_tone(beacon_id: str, duration: float = 3.0, 
                         sample_rate: int = SAMPLE_RATE) -> np.ndarray:
    """
    Generate complete ultrasonic beacon signal.
    
    Structure:
    1. Preamble (sync signal)
    2. Length byte (8 bits)
    3. Beacon ID (FSK encoded)
    4. Checksum (8 bits)
    5. Padding silence
    """
    # Encode beacon ID to bits
    beacon_bits = string_to_bits(beacon_id)
    
    # Add length byte
    length_bits = [(len(beacon_id) >> i) & 1 for i in range(7, -1, -1)]
    
    # Calculate simple checksum (XOR of all bytes)
    checksum = 0
    for char in beacon_id:
        checksum ^= ord(char)
    checksum_bits = [(checksum >> i) & 1 for i in range(7, -1, -1)]
    
    # Combine all bits
    all_bits = length_bits + beacon_bits + checksum_bits
    
    # Generate signals
    preamble = generate_preamble(sample_rate)
    data_signal = generate_fsk_signal(all_bits, sample_rate)
    
    # Calculate total duration
    signal_duration = len(preamble) / sample_rate + len(data_signal) / sample_rate
    
    # Add padding if needed
    if duration > signal_duration:
        padding_samples = int((duration - signal_duration) * sample_rate)
        padding = np.zeros(padding_samples)
        full_signal = np.concatenate([preamble, data_signal, padding])
    else:
        full_signal = np.concatenate([preamble, data_signal])
    
    # Normalize to prevent clipping
    full_signal = full_signal / np.max(np.abs(full_signal)) * 0.8
    
    return full_signal


def save_wav(signal_data: np.ndarray, filename: str, 
             sample_rate: int = SAMPLE_RATE) -> None:
    """Save signal as 16-bit WAV file."""
    # Convert to 16-bit integer
    signal_int = (signal_data * 32767).astype(np.int16)
    wavfile.write(filename, sample_rate, signal_int)


def main():
    parser = argparse.ArgumentParser(
        description='Generate ultrasonic beacon tones for EchoTrack'
    )
    parser.add_argument(
        '--beacon-id', '-b', 
        required=True,
        help='Beacon ID to encode (e.g., "ABC123")'
    )
    parser.add_argument(
        '--output', '-o',
        default='beacon_tone.wav',
        help='Output WAV filename'
    )
    parser.add_argument(
        '--duration', '-d',
        type=float,
        default=3.0,
        help='Total duration in seconds (default: 3.0)'
    )
    parser.add_argument(
        '--loop', '-l',
        type=int,
        default=1,
        help='Number of times to repeat the beacon (default: 1)'
    )
    parser.add_argument(
        '--verbose', '-v',
        action='store_true',
        help='Print detailed information'
    )
    
    args = parser.parse_args()
    
    if args.verbose:
        print(f"Generating beacon tone for ID: {args.beacon_id}")
        print(f"FSK Parameters:")
        print(f"  Low frequency (0):  {FREQ_LOW} Hz")
        print(f"  High frequency (1): {FREQ_HIGH} Hz")
        print(f"  Symbol duration:    {SYMBOL_DURATION * 1000} ms")
        print(f"  Sample rate:        {SAMPLE_RATE} Hz")
    
    # Generate signal
    single_signal = generate_beacon_tone(
        args.beacon_id, 
        args.duration / args.loop,
        SAMPLE_RATE
    )
    
    # Repeat if requested
    if args.loop > 1:
        signal_data = np.tile(single_signal, args.loop)
    else:
        signal_data = single_signal
    
    # Save to file
    save_wav(signal_data, args.output, SAMPLE_RATE)
    
    print(f"✓ Generated: {args.output}")
    print(f"  Beacon ID: {args.beacon_id}")
    print(f"  Duration:  {len(signal_data) / SAMPLE_RATE:.2f} seconds")
    print(f"  Frequency: 18.5-19.5 kHz (inaudible)")
    
    if args.verbose:
        bits = string_to_bits(args.beacon_id)
        print(f"  Encoded bits: {len(bits) + 16} ({len(bits)} data + 16 header)")


if __name__ == '__main__':
    main()
