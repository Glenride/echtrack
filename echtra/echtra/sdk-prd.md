# EchoTrack Mobile SDK PRD

Embeddable SDK for partner apps to enable ultrasonic beacon detection.

---

## 1. Overview

**Project:** EchoTrack SDK (Native Swift + Kotlin)  
**Goal:** Drop-in library that partner apps integrate to monetize via beacon tracking.

This is the core revenue product - licensed to app developers (reward apps, utility apps).

---

## 2. Tech Stack

| Platform | Language | Min Version |
|----------|----------|-------------|
| iOS | Swift 5.9 | iOS 14.0+ |
| Android | Kotlin 1.9 | Android 8.0 (API 26)+ |

**Distribution:**
- iOS: Swift Package Manager (SPM), CocoaPods
- Android: Maven Central
- React Native: Wrapper module

---

## 3. Core Features

### 3.1 Detection Modes

| Mode | Description | Battery Impact |
|------|-------------|----------------|
| **Active** | Listen when app is open | Low |
| **Background** | Wake every 15min, listen 10s | Very Low |
| **Geofence** | Listen when near target locations | Minimal |

### 3.2 Privacy First
- Discard all audio < 16kHz immediately
- No voice data processed or stored
- Only binary beacon payloads extracted
- GDPR/CCPA compliant consent flow

### 3.3 Explicit Opt-In
- SDK provides consent UI components
- "Listen & Earn" value exchange messaging
- Easy opt-out at any time

---

## 4. SDK API

### iOS (Swift)
```swift
import EchoTrackSDK

// Initialize with API key
EchoTrack.configure(apiKey: "eck_xxxxx")

// Request permission with consent UI
EchoTrack.requestPermission { granted in
    if granted {
        EchoTrack.startListening()
    }
}

// Listen for detections
EchoTrack.onBeaconDetected { beacon in
    print("Detected: \(beacon.id) at \(beacon.signalStrength)dB")
}

// Stop listening
EchoTrack.stopListening()
```

### Android (Kotlin)
```kotlin
import com.echotrack.sdk.EchoTrack

// Initialize in Application class
EchoTrack.configure(this, "eck_xxxxx")

// Request permission
EchoTrack.requestPermission(activity) { granted ->
    if (granted) {
        EchoTrack.startListening()
    }
}

// Listen for detections
EchoTrack.onBeaconDetected { beacon ->
    Log.d("EchoTrack", "Detected: ${beacon.id}")
}
```

### React Native Wrapper
```javascript
import { EchoTrack } from 'echotrack-react-native';

await EchoTrack.configure('eck_xxxxx');
await EchoTrack.requestPermission();
EchoTrack.startListening();

EchoTrack.onBeaconDetected((beacon) => {
  console.log('Detected:', beacon.id);
});
```

---

## 5. Technical Requirements

### Audio Processing
- FFT on-device (no cloud processing)
- Process 48kHz audio in 85ms windows
- Bandpass filter: 17-21kHz
- Signal detection threshold: -60dB

### Network
- Batch events locally (up to 100)
- Sync when on WiFi or every 4 hours
- Retry with exponential backoff
- Offline queue with 30-day retention

### Battery Optimization
- Use accelerometer to pause when phone stationary
- Reduce sample rate in background mode
- iOS: Use `beginBackgroundTask` sparingly
- Android: Use WorkManager for periodic checks

---

## 6. Platform-Specific Considerations

### iOS
- Handle orange dot indicator gracefully
- Request microphone permission with clear rationale
- Support iOS audio session categories
- Handle interruptions (calls, Siri)

### Android
- Handle foreground service notifications
- Request RECORD_AUDIO permission
- Battery optimization whitelisting guidance
- Support various audio hardware configurations

---

## 7. SDK Size

| Platform | Target Size |
|----------|-------------|
| iOS | < 2 MB |
| Android | < 1.5 MB |

---

## 8. Distribution

### iOS
```ruby
# Podfile
pod 'EchoTrackSDK', '~> 1.0'

# Package.swift
.package(url: "https://github.com/echotrack/sdk-ios", from: "1.0.0")
```

### Android
```kotlin
// build.gradle
implementation 'com.echotrack:sdk:1.0.0'
```

---

## 9. Project Structure

### iOS SDK
```
echotrack-sdk-ios/
├── Sources/EchoTrackSDK/
│   ├── EchoTrack.swift           # Main entry point
│   ├── AudioProcessor.swift      # FFT processing
│   ├── FSKDecoder.swift          # Beacon decoding
│   ├── BeaconManager.swift       # Detection logic
│   ├── PermissionManager.swift   # Microphone permissions
│   ├── NetworkManager.swift      # API communication
│   └── Models/
│       ├── Beacon.swift
│       └── DetectionEvent.swift
├── Package.swift
└── Tests/
```

### Android SDK
```
echotrack-sdk-android/
├── sdk/src/main/kotlin/com/echotrack/sdk/
│   ├── EchoTrack.kt              # Main entry point
│   ├── AudioProcessor.kt         # FFT processing
│   ├── FSKDecoder.kt             # Beacon decoding
│   ├── BeaconManager.kt          # Detection logic
│   ├── PermissionHelper.kt       # Runtime permissions
│   └── NetworkManager.kt         # API communication
├── build.gradle.kts
└── sdk/src/test/
```

---

## 10. Development Phases

### Phase 1: Core Audio (Week 1-2)
- [ ] iOS: Audio capture with AVAudioEngine
- [ ] Android: Audio capture with AudioRecord
- [ ] FFT implementation (Accelerate/KissFFT)
- [ ] Basic frequency detection

### Phase 2: FSK Decoder (Week 3)
- [ ] Preamble detection algorithm
- [ ] Symbol decoding (0/1)
- [ ] Beacon ID extraction
- [ ] Checksum validation

### Phase 3: SDK API (Week 4)
- [ ] Public API surface
- [ ] Permission handling
- [ ] Event callbacks
- [ ] Configuration options

### Phase 4: Networking (Week 5)
- [ ] API key authentication
- [ ] Event batching
- [ ] Offline queue
- [ ] Retry logic

### Phase 5: Polish (Week 6)
- [ ] Battery optimization
- [ ] Background modes
- [ ] Documentation
- [ ] Sample apps

---

## 11. Revenue Model

| Tier | Price/Month | MAU Limit |
|------|-------------|-----------|
| Starter | $99 | 10,000 |
| Growth | $499 | 100,000 |
| Enterprise | Custom | Unlimited |

Partner apps earn revenue share on attributions driven through their SDK integration.
