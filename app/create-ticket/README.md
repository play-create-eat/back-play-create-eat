# Wristband Printer Application

This web application allows you to print wristbands using a Zebra ZD411D printer. It generates wristbands with custom text and QR codes.

## Features

- Custom text input
- QR code generation
- Preview functionality
- ngrok integration for remote access
- Modern UI with Tailwind CSS

## Prerequisites

- Python 3.7 or higher
- Zebra ZD411D printer
- ngrok account (for remote access)

## Installation

1. Clone this repository
2. Install the required dependencies:
   ```bash
   pip install -r requirements.txt
   ```

## Usage

1. Start the application:
   ```bash
   python app.py
   ```

2. The application will start and display a public URL from ngrok
3. Open the provided URL in your web browser
4. Enter the custom text and QR code data
5. Click "Print Wristband" to generate and print the wristband

## Printer Setup

1. Connect your Zebra ZD411D printer to your computer
2. Ensure the printer is properly configured for Z-Band Fun wristbands
3. The printer should be set as the default printer or configured in the application

## Notes

- The application uses ngrok to create a public URL, allowing access from any device
- Make sure your printer is properly connected and configured before printing
- The preview feature shows how the wristband will look before printing 