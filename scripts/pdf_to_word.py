
import sys
from pathlib import Path
from pdf2docx import Converter


def convert_pdf_to_word(input_file, output_file):

    input_path = Path(input_file)
    output_path = Path(output_file)

    if not input_path.exists():
        raise FileNotFoundError("Input PDF file not found.")

    converter = Converter(str(input_path))

    try:
        converter.convert(str(output_path))
    finally:
        converter.close()

    if not output_path.exists():
        raise RuntimeError("DOCX file was not generated.")

    print("Conversion successful.")


if __name__ == "__main__":

    if len(sys.argv) != 3:
        print("Usage: python pdf_to_word.py input.pdf output.docx")
        sys.exit(1)

    try:
        convert_pdf_to_word(sys.argv[1], sys.argv[2])

    except Exception as error:
        print(str(error), file=sys.stderr)
        sys.exit(1)
