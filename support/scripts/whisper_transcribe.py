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

parser = argparse.ArgumentParser(description='Transcribe audio')

parser.add_argument('--input', nargs=1,
                    help='Path to file to transcribe', required=True)
parser.add_argument('--output', nargs=1,
                    help='Path to file of vtt transcription', required=True)
parser.add_argument('--model', nargs=1, default='base',
                    help='Specify whisper model size, base model set as default')

args = parser.parse_args()

def transcribe_audio(input, output, model):
    vttFilename = output
    if pathlib.PurePath(vttFilename).suffix != '.vtt':
        vttFilename = vttFilename + '.vtt'
    
    model = whisper.load_model(model)
    
    try:
        transcribe = model.transcribe(audio=input)
    except RuntimeError as e:
        print("[whisper_transcribe.py] Could not transcribe file: " + str(e))
        quit(2)
    segments = transcribe['segments']
    with open(vttFilename, 'w', encoding='utf-8') as vttFile:
        vttFile.write("WEBVTT\n\n")
        for segment in segments:
            startTime = str(0)+str(timedelta(seconds=int(segment['start'])))+'.000'
            endTime = str(0)+str(timedelta(seconds=int(segment['end'])))+'.000'
            text = segment['text']
            segmentId = segment['id']+1
            segment = f"{segmentId}\n{startTime} --> {endTime}\n{text[1:] if text[0] == ' ' else text}\n\n"

            vttFile.write(segment)

    return vttFilename

transcribe_audio(args.input[0], args.output[0], args.model)
