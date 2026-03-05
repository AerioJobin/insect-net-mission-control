# Contributing to Insect NET: Mission Control

Thank you for your interest in contributing to the Insect NET project!

## 📋 Project Documentation

Before you start contributing, please review our project documentation:

- **[Technical Progress Report](docs/TECHNICAL_PROGRESS_REPORT.md)** - Comprehensive system architecture, features, and development roadmap
- **[README](README.md)** - Project overview and quick start guide
- **[LICENSE](LICENSE)** - MIT License

## 🎯 How to Contribute

### 1. Development Setup
```bash
git clone https://github.com/AerioJobin/insect-net-mission-control.git
cd insect-net-mission-control
```

### 2. Project Structure
- `web/` - Frontend dashboard (PHP/JavaScript)
- `scripts/` - Backend processing (Python)
- `cloud/` - AWS Lambda functions
- `hardware/` - IoT device firmware (ESP32)
- `docs/` - Technical documentation

### 3. Code Guidelines

#### Python (Backend)
- Follow PEP 8 style guide
- Add docstrings to all functions
- Include type hints
```python
def process_sqs_message(message: dict) -> bool:
    """Process SQS message from S3 bucket."""
    pass
```

#### PHP (Frontend)
- Use consistent indentation (4 spaces)
- Add comments for complex logic
- Follow PSR-12 coding standard

#### JavaScript
- Use ES6+ syntax
- Include JSDoc comments for functions
- Maintain modular structure

### 4. Commit Message Format
```
<type>(<scope>): <subject>

<body>

<footer>
```

Types:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `refactor:` Code refactoring
- `test:` Test additions/updates

Example:
```
feat(dashboard): add real-time battery alerts

Implement WebSocket connection for live battery voltage updates

Closes #42
```

### 5. Pull Request Process

1. Create a feature branch: `git checkout -b feature/your-feature-name`
2. Commit your changes with meaningful messages
3. Push to your fork: `git push origin feature/your-feature-name`
4. Open a Pull Request with a clear description
5. Ensure all tests pass
6. Address code review feedback

### 6. Testing

#### Backend Testing
```bash
python3 -m pytest scripts/tests/
```

#### Frontend Testing
```bash
npm test web/
```

## 🚀 Current Development Phases

### Phase 2: AI Integration (In Progress)
- YOLOv8 pest detection model integration
- Species classification API
- Real-time pest density heatmaps

### Phase 3: Field Testing (Planned)
- Hardware prototype deployment
- Low-bandwidth scenario testing
- Solar charging validation

### Phase 4: Multi-user System (Planned)
- User authentication and authorization
- Multi-site management
- Advanced analytics dashboard

## 🐛 Reporting Bugs

When reporting bugs, please include:

1. **Description**: Clear explanation of the issue
2. **Steps to Reproduce**: Specific steps to trigger the bug
3. **Expected Behavior**: What should happen
4. **Actual Behavior**: What actually happens
5. **Environment**: OS, Python version, PHP version, etc.
6. **Screenshots**: If applicable

## 💡 Feature Requests

Feature requests are welcome! Please provide:

1. **Use Case**: Why is this feature needed?
2. **Proposed Solution**: How should it work?
3. **Alternative Solutions**: Any alternatives considered?
4. **Related Issues**: Links to similar issues or discussions

## 📞 Questions or Need Help?

- **Lab Contact**: NeuRonICS Lab, Indian Institute of Science (IISc)
- **Email**: neuronics.iisc@iisc.ac.in
- **Project Lead**: Aerio Jobin G Momin

## 📄 License

By contributing to this project, you agree that your contributions will be licensed under its MIT License.

---

**Thank you for making Insect NET better! 🙌**
