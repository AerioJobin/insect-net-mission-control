# 🔬 Insect NET: Mission Control
**Autonomous Pest Monitoring & Telemetry Platform** *NeuRonICS Lab — Indian Institute of Science (IISc)*

## 🚀 Overview
Insect NET is an IoT ecosystem designed to monitor agricultural pests in real-time. 
This repository contains the cloud infrastructure, backend processing workers, 
and the live dashboard used for fleet management.

## 🏗 System Architecture
- **Hardware:** ESP32-CAM (captures & uploads via Pre-signed URLs).
- **Cloud:** AWS S3 (Storage) + SQS (Messaging) + Lambda (Auth Bridge).
- **Backend:** Python "Scientist" worker running on EC2.
- **Frontend:** PHP/JS Dashboard with live Battery Analytics & GPS mapping.

## 🛠 Setup & Installation
1. **Server:** Deploy `web/` folder to `/var/www/html/` on EC2.
2. **Worker:** Run `python3 scripts/scientist_worker.py` using `nohup`.
3. **Lambda:** Deploy `cloud/lambda_presign.py` as a Function URL.

## 👥 Contributors
- **Project Lead:** [Your Name]
- **Lab:** NeuRonICS Lab, IISc
