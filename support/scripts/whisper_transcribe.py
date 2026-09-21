#!/usr/bin/env python3
from datetime import timedelta
import os
import pathlib
import argparse
import json
try:
    import whisper
except ModuleNotFoundError:
    print("The whisper module is not installed. Please install using pip3 install git+https://github.com/openai/whisper.git. See https://github.com/openai/whisper for more information.")
    quit(5)

parser = argparse.ArgumentParser(description='Transcribe audio')

parser.add_argument('--input', nargs=1,
                    help='Path to file to transcribe', required=True)
parser.add_argument('--output', nargs=1,
                    help='Path to file of vtt transcription', required=True)
parser.add_argument('--model', nargs=1, default='base',
                    help='Specify whisper model size, base model set as default')
parser.add_argument('--tmpdir', nargs=1, default='/tmp',
                                    help='Specify whisper temporary directory')
parser.add_argument('--format', nargs=1, default='vtt',
                    help='Output format, vtt set as default')
                    
parser.add_argument('--words', nargs=1, default=False,
                    help='Output word timestamps, false as default')

args = parser.parse_args()

def transcribe_audio(input, output, model, tmpdir, format, words):
    filename = output
    if pathlib.PurePath(filename).suffix != ('.' + format):
        filename = filename + '.' + format
    
    if isinstance(model, list):
        model = model[0]
    model = whisper.load_model(model, None, tmpdir)
    
    try:
        transcribe = model.transcribe(audio=input, word_timestamps=words)
    except RuntimeError as e:
        print("[whisper_transcribe.py] Could not transcribe file: " + str(e))
        quit(2)
        
    if format == 'vtt':
        segments = transcribe['segments']
        with open(filename, 'w', encoding='utf-8') as vttFile:
            vttFile.write("WEBVTT\n\n")
            for segment in segments:
                startTime = str(0)+str(timedelta(seconds=int(segment['start'])))+'.000'
                endTime = str(0)+str(timedelta(seconds=int(segment['end'])))+'.000'
                text = segment['text']
                segmentId = segment['id']+1
                segment = f"{segmentId}\n{startTime} --> {endTime}\n{text[1:] if (len(text) and text[0] == ' ') else text}\n\n"
    
                vttFile.write(segment)
    elif format == 'json':
        with open(filename, 'w', encoding='utf-8') as jsonFile:
            json.dump(transcribe, jsonFile, indent=4, ensure_ascii=False)
    else:
        print("Invalid format")
        quite(3)
        
    return filename

transcribe_audio(args.input[0], args.output[0], args.model, args.tmpdir[0], args.format[0], args.words)
