# Technical Progress Report: Insect NET IoT System

**Project:** Insect NET IoT Monitoring System  
**Lab:** NeuRonICS Lab, Indian Institute of Science (IISc)  
**Date:** March 5, 2026  
**Status:** Phase 1 Infrastructure & Dashboard — Ongoing

---

## 1. Executive Summary

The backend infrastructure and centralized monitoring dashboard for the Insect NET project are now **fully operational**. We have successfully established a real-time data pipeline between field-deployed hardware via AWS S3/SQS and a branded web interface hosted on an Amazon EC2 instance. The system is currently capable of receiving, processing, and visualizing insect captures and battery telemetry.

---

## 2. System Architecture

The platform utilizes a **Decoupled Cloud Architecture** to ensure high availability and scalability for multiple field units.

### 2.1 Ingestion Layer: AWS S3 + SQS
Data is received via an **S3 Bucket** (`insect-net-bucket`), serving as the primary repository for:
- High-resolution pest capture images
- Telemetry JSON files (GPS, battery voltage, temperature)

An **SQS Simple Queue Service** trigger is active, ensuring the server only processes data when new files are detected.

### 2.2 Processing Layer: Scientist Worker
A custom Python daemon (`scientist_worker.py`) runs in the background. It:
- Automatically intercepts SQS messages
- Downloads new assets from S3
- Sorts them into device-specific directories
- Maintains a local cache for fast retrieval

### 2.3 Visualization Layer: Mission Control Dashboard
Built with **PHP, JavaScript (Chart.js), and Leaflet.js**. The dashboard provides:
- Real-time GPS tracking on an interactive map
- Dynamic battery monitoring with 10-point voltage trend graphs
- Device status overview grid
- Automated image gallery organized by Device ID

---

## 3. Key Features Implemented

| Feature | Description | Status |
|---------|-------------|--------|
| **Fleet Overview** | High-level map and status grid for all active INMT devices | ✅ Active |
| **Real-Time Sync** | Background worker syncing S3 data to EC2 local storage | ✅ Active |
| **Battery Analytics** | Live voltage monitoring with historical trend graphing | ✅ Active |
| **Image Gallery** | Automated sorting of pest captures by Device ID | ✅ Active |
| **Lab Branding** | Official IISc and NeuRonICS Lab asset integration | ✅ Active |
| **AI Integration Ready** | Placeholder for YOLOv8 pest detection (api_count.php) | 🔄 Pending |

---

## 4. Security Implementation

### 4.1 Access Control
- **SSH Key Authentication:** Access to the EC2 server is restricted via `.pem` key pairs
- **File Permissions:** Sensitive credentials excluded from repository via `.gitignore`

### 4.2 Asset Protection
- Laboratory logos and UI components are centralized in a secured `/assets` directory
- Database strategy uses file-based JSON caching (no exposed credentials)

### 4.3 Authentication Ready
The system is prepared for **Multi-User Email/Password login integration** to protect sensitive research data.

---

## 5. Current Technical Specifications

| Component | Details |
|-----------|----------|
| **Host IP** | 65.2.30.116 |
| **Web Server** | Apache (Linux/Unix) |
| **Database** | File-based JSON caching for high-speed retrieval |
| **Frontend Stack** | HTML5, CSS3, Inter/Space Mono Typography |
| **Backend** | PHP 8.x, Python 3.x |
| **Cloud Services** | AWS S3, SQS, EC2, Lambda (presigned URLs) |

---

## 6. Repository Structure

```
insect-net-mission-control/
├── .github/                  # GitHub configuration
├── assets/                   # IISc & Lab logos, UI images
├── cloud/
│   └── lambda_presign.py     # AWS Lambda for S3 upload bridge
├── hardware/                 # (Placeholder) Arduino/ESP32 firmware
├── scripts/
│   └── scientist_worker.py   # SQS/S3 processing daemon
├── web/
│   ├── index.php             # Dashboard UI & Login logic
│   ├── get_status.php        # JSON API for live device status
│   ├── api_count.php         # (Future) AI integration point
│   └── uploads/              # Local synced images (gitignored)
├── docs/
│   └── TECHNICAL_PROGRESS_REPORT.md  # This file
├── .gitignore                # Excludes: .pem keys, .env, sensitive files
├── LICENSE                   # MIT License
└── README.md                 # Project overview
```

---

## 7. Next Milestones

### Phase 2: AI Integration
- [ ] Connect YOLOv8 pest detection model to `api_count.php`
- [ ] Implement real-time species classification on detected insects
- [ ] Add pest density heatmap overlay on map view

### Phase 3: Field Stress Testing
- [ ] Deploy first physical INMT prototype to verify S3 upload stability
- [ ] Test system in low-bandwidth field conditions
- [ ] Validate battery life under real-world solar charging conditions

### Phase 4: Alerting & Automation
- [ ] Implement automated email notifications upon high-density pest detection
- [ ] Add SMS alerts for critical battery warnings
- [ ] Integrate multi-user permission system for lab administrators

---

## 8. Known Limitations & Future Improvements

1. **Database Scalability:** Current file-based JSON caching suitable for <100 devices. PostgreSQL integration planned for larger deployments.
2. **Real-time Latency:** SQS polling interval currently 60 seconds. WebSocket integration under consideration for <5 second latency.
3. **Mobile Support:** Dashboard currently optimized for desktop. Responsive redesign in progress.
4. **Offline Mode:** Cached images unavailable if server goes offline. Implement peer-to-peer sync between field units.

---

## 9. Conclusion

The Insect NET Mission Control infrastructure is production-ready for Phase 1 field deployments. The modular architecture allows for seamless integration of AI models, additional sensors, and multi-site management as the project scales.

**Next Review:** April 15, 2026

---

**Prepared by:** Aerio Jobin G Momin  
**Organization:** NeuRonICS Lab, Indian Institute of Science (IISc)  
**Contact:** neuronics.iisc@iisc.ac.in
