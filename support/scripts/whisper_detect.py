#!/usr/bin/env python3
from datetime import timedelta
import os
import pathlib
import argparse
try:
    import whisper
except ModuleNotFoundError:
    print("The whisper module is not installed. Please install using pip3 install git+https://github.com/openai/whisper.git. See https://github.com/openai/whisper for more information.")
    quit(5)

parser = argparse.ArgumentParser(description='Detect spoken language in audio')

parser.add_argument('--input', nargs=1,
                    help='Path to file to transcribe', required=True)
parser.add_argument('--model', nargs=1, default='base',
                    help='Specify whisper model size, base model set as default')
parser.add_argument('--tmpdir', nargs=1, default='/tmp',
                            help='Specify whisper temporary directory')

args = parser.parse_args()

def detect(input, model, tmpdir):
    model = whisper.load_model(model, None, tmpdir)
    audio = whisper.load_audio(input)
    audio = whisper.pad_or_trim(audio)
    mel = whisper.log_mel_spectrogram(audio).to(model.device)
    
    try:
        _, probs = model.detect_language(mel)
        print(f"{max(probs, key=probs.get)}")
    except RuntimeError as e:
        print("[whisper_transcribe.py] Could not detect spoken language in file: " + str(e))
        quit(2)
    
    return probs.get

detect(args.input[0], args.model, args.tmpdir[0])
