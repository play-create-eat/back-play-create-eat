import sys
import subprocess
import os

def print_zpl(zpl_data):
    try:
        # Create a temporary file with the ZPL data
        temp_file = 'temp_label.zpl'
        with open(temp_file, 'w') as f:
            f.write(zpl_data)
        
        # Use lp command to print to the default printer
        result = subprocess.run(['lp', temp_file], 
                              capture_output=True, 
                              text=True)
        
        # Clean up the temporary file
        os.remove(temp_file)
        
        if result.returncode == 0:
            print("Print job sent successfully!")
            return True
        else:
            print(f"Print error: {result.stderr}")
            return False
    except Exception as e:
        print(f"Printer error: {str(e)}")
        return False

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python print_client.py <zpl_file>")
        sys.exit(1)
    
    zpl_file = sys.argv[1]
    try:
        with open(zpl_file, 'r') as f:
            zpl_data = f.read()
        print_zpl(zpl_data)
    except Exception as e:
        print(f"Error reading file: {str(e)}")
        sys.exit(1) 