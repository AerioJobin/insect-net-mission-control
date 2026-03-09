# Firmware Phase 1 - Camera Uploader

This firmware handles VGA image capture and upload to AWS S3 using the SIM7670G cellular modem.

## Hardware Setup
- **ESP32-S3**: Main controller.
- **SIM7670G**: Cellular modem over UART (pins 17/18).
- **ArduCam 5MP**: Camera over SPI (pins 10-13).

## System Workflow

### 1. Boot Sequence (`setup()`)
- Starts UART for modem communication.
- Power-checks the modem via `testAT()`; pulses `PWRKEY` if needed and waits 12s for boot.
- Connects to internet via GPRS (APN: `airtelgprs.com`).
- Initializes camera and waits 5s before starting the loop.

### 2. Main Loop (`loop()`)
- Executes `captureAndUpload()` every 60 seconds.
- Automatically reconnects to the network if the connection is lost.

### 3. Capture Cycle (`captureAndUpload()`)
- Captures a VGA JPEG photo into a heap buffer.
- Generates a unique filename using `millis()` (e.g., `INMT_9529.jpg`).
- Coordinates the upload process and frees memory after completion.

### 4. Getting Presigned URL (`getPresignedUrl()`)
- Uses the modem's HTTP stack (`AT+HTTPINIT`) to request a PUT URL from an AWS Lambda function.
- Reads the response body directly from UART to prevent data loss.
- Extracts the clean URL (approx. 315 chars) by parsing the raw response.

### 5. Upload to S3 (`putImageToS3()`)
- **Direct Socket Innovation**: Since the presigned URL exceeds the modem's `AT+HTTPPARA` character limit, this uses raw SSL sockets.
- Initializes the SSL stack with `AT+CCHSTART`.
- Opens a direct TLS connection to the S3 bucket on port 443.
- Manually constructs and sends raw HTTP/1.1 PUT headers.
- Streams the JPEG data in 1KB chunks using `AT+CCHSEND`.
- Validates success by checking for an HTTP 200 response.

## Helper Functions
- `sendAT()`: Sends AT commands and waits for response silently.
- `waitFor()`: Accumulates UART bytes until a specific target string or timeout is reached.
