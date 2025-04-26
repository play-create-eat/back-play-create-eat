from flask import Flask, render_template, request, jsonify, send_file
import qrcode
import io
import base64
import os
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
import tempfile
from PIL import Image

app = Flask(__name__)

def generate_qr_code(data):
    try:
        # Calculate QR code size in pixels (22mm at 300 DPI)
        # 22mm * 300DPI / 25.4mm = 260 pixels
        size_pixels = int(22 * 300 / 25.4)
        
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_L,
            box_size=10,
            border=4,
        )
        qr.add_data(data)
        qr.make(fit=True)
        img = qr.make_image(fill_color="black", back_color="white")
        
        # Resize the image to exact dimensions
        img = img.resize((size_pixels, size_pixels), Image.Resampling.LANCZOS)
        return img
    except Exception as e:
        print(f"Error generating QR code: {str(e)}")
        raise

def create_pdf_with_qr(qr_data):
    try:
        # Create a temporary file for the PDF
        temp_pdf = tempfile.NamedTemporaryFile(delete=False, suffix='.pdf')
        temp_pdf_path = temp_pdf.name
        temp_pdf.close()

        # Create PDF with specific dimensions (254mm x 25.4mm)
        c = canvas.Canvas(temp_pdf_path, pagesize=(25.4*mm, 254*mm))
        
        # Generate QR code
        qr_img = generate_qr_code(qr_data)
        
        # Save QR code to a temporary PNG
        temp_qr = tempfile.NamedTemporaryFile(delete=False, suffix='.png')
        qr_img.save(temp_qr.name)
        
        # Add QR code to PDF
        # Position it in the center of the page
        c.drawImage(temp_qr.name, 
                   (25.4*mm - 24*mm)/2,  # Center horizontally
                   (254*mm - 24*mm) - 78,  # Center vertically
                   width=24*mm,
                   height=24*mm)
        
        # Add horizontal logo first
        logo_long_path = os.path.join(app.static_folder, 'images', 'logo-long.png')
        if os.path.exists(logo_long_path):
            print(f"Looking for logo-long at: {logo_long_path}")  # Debug log
            try:
                # Open and process the horizontal logo with PIL to ensure transparency
                logo_long_img = Image.open(logo_long_path)
                print(f"Original image size: {logo_long_img.size}")  # Debug log
                
                # Convert to RGBA if not already
                if logo_long_img.mode != 'RGBA':
                    logo_long_img = logo_long_img.convert('RGBA')
                
                # Rotate the image 90 degrees clockwise
                logo_long_img = logo_long_img.rotate(90, expand=True)
                print(f"Rotated image size: {logo_long_img.size}")  # Debug log
                
                # Save processed horizontal logo to temporary file
                temp_logo_long = tempfile.NamedTemporaryFile(delete=False, suffix='.png')
                logo_long_img.save(temp_logo_long.name, 'PNG')
                print(f"Saved temporary logo to: {temp_logo_long.name}")  # Debug log
                
                # Set dimensions for vertical logo (original 150x17, now rotated)
                logo_long_width = 17*mm  # Original height becomes width
                logo_long_height = 150*mm  # Original width becomes height
                
                # Position vertical logo below QR code (10mm gap)
                c.drawImage(temp_logo_long.name,
                           (25.4*mm - logo_long_width)/2,  # Center horizontally
                           (254*mm - 24*mm) - 78 - logo_long_height - 10*mm,  # Below QR code with 10mm gap
                           width=logo_long_width,
                           height=logo_long_height)
                print("Drew logo_long to PDF")  # Debug log
                
                # Clean up the temporary horizontal logo file
                os.unlink(temp_logo_long.name)
                
                # Now add regular logo
                logo_path = os.path.join(app.static_folder, 'images', 'logo.png')
                if os.path.exists(logo_path):
                    # Open and process the logo with PIL to ensure transparency
                    logo_img = Image.open(logo_path)
                    
                    # Convert to RGBA if not already
                    if logo_img.mode != 'RGBA':
                        logo_img = logo_img.convert('RGBA')
                    
                    # Rotate the logo 90 degrees clockwise
                    logo_img = logo_img.rotate(90, expand=True)
                    
                    # Save processed logo to temporary file
                    temp_logo = tempfile.NamedTemporaryFile(delete=False, suffix='.png')
                    logo_img.save(temp_logo.name, 'PNG')
                    
                    # Calculate logo dimensions
                    logo_width = 22*mm
                    logo_height = (logo_width * 20) / 24
                    
                    # Position logo below logo_long (5mm gap)
                    c.drawImage(temp_logo.name,
                               (25.4*mm - logo_width)/2,  # Center horizontally
                               (254*mm - 24*mm) - 78 - logo_long_height - 10*mm - logo_height - 5*mm,  # Below logo_long with 5mm gap
                               width=logo_width,
                               height=logo_height)
                    
                    # Clean up the temporary logo file
                    os.unlink(temp_logo.name)
            except Exception as e:
                print(f"Error processing logos: {str(e)}")  # Debug log
        else:
            print(f"logo_long.png not found at: {logo_long_path}")  # Debug log
        
        # Save the PDF
        c.save()
        
        # Clean up the temporary QR code file
        os.unlink(temp_qr.name)
        
        return temp_pdf_path
    except Exception as e:
        print(f"Error creating PDF: {str(e)}")
        raise

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/print', methods=['POST'])
def print_label():
    try:
        data = request.json
        qr_data = data.get('qr_data', '')
        
        if not qr_data:
            return jsonify({'error': 'QR data is required'}), 400
        
        # Create PDF with QR code
        pdf_path = create_pdf_with_qr(qr_data)
        
        # Read the PDF file and convert to base64
        with open(pdf_path, 'rb') as pdf_file:
            pdf_content = pdf_file.read()
            pdf_base64 = base64.b64encode(pdf_content).decode('utf-8')
        
        # Clean up the temporary PDF file
        os.unlink(pdf_path)
        
        return jsonify({
            'success': True,
            'pdf': pdf_base64
        })
    
    except Exception as e:
        print(f"Error in print_label route: {str(e)}")
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True, port=5002) 