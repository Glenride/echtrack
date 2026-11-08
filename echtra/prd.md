This Product Requirements Document (PRD) outlines the specifications for the **Ultrasonic Attribution & Retargeting Platform** (internally referred to as "EchoTrack").

This system is designed to link digital ad exposure to physical foot traffic using high-frequency audio triggers.

### **1. Executive Summary**

**EchoTrack** is a cross-device attribution platform that utilizes non-audible ultrasonic frequencies (18kHz–22kHz) to bridge the gap between offline media (TV, Radio, In-Store PA) and mobile user behavior.

* **Primary Goal:** To provide advertisers with deterministic proof that their broadcast ads drove physical store visits or "second screen" engagement.
* **Key Differentiator:** Precise room-level and proximity-based detection that bypasses the limitations of GPS and Bluetooth.

---

### **2. Target Audience**

1. **Advertisers (Clients):** Retail brands, automotive manufacturers, and QSR (Quick Service Restaurants) wanting to measure ROI on TV/Radio spend.
2. **Publishers (Partners):** Mobile app developers (Reward apps, Utility apps) looking to monetize their user base by embedding our Receiver SDK.
3. **End Users (Consumers):** Mobile users who opt-in to "Listen & Earn" rewards via partner apps.

---

### **3. System Architecture & Tech Stack**

Based on your preferred ecosystem, the stack prioritizes rapid development and scalability.

* **Frontend (Dashboard & Demo App):** **React Expo**.
* *Why:* Allows for building the advertiser dashboard and the "Test/Demo" mobile app (iOS/Android) from a single codebase.


* **Backend API & DMP:** **Laravel**.
* *Why:* Robust handling of high-volume API requests (ingesting beacon hits), excellent queue management for processing signals, and rapid API development.


* **AI/Analysis Layer:** **OpenAI API**.
* *Why:* Used to analyze aggregate user dwell times and categorize "Location Context" (e.g., converting raw beacon data into semantic tags like "High-Intent Shopper").


* **Mobile SDK (The Core Product):** Native **Swift** (iOS) and **Kotlin** (Android), wrapped into a module compatible with React Native/Expo for easy integration.

---

### **4. Core Functional Modules**

#### **Module A: The Emitter (Audio Watermarker)**

A web-based tool allowing advertisers to inject data into their audio assets.

* **Input:** User uploads `.mp3` or `.wav` (Commercial spot or In-store loop).
* **Processing:**
* Generates a unique 16-bit **Campaign ID** or **Location ID**.
* Encodes the ID into the 18kHz–20kHz spectrum using Frequency Shift Keying (FSK).


* **Output:** Returns the "Watermarked" audio file ready for broadcast.
* **Requirement:** The ultrasonic layer must be robust enough to survive compression (AAC/MP3) and air transmission (TV speakers to Phone mic).

#### **Module B: The Receiver (Mobile SDK)**

A lightweight background service integrated into partner apps.

* **State Management:**
* *Active Mode:* Listens when the app is open.
* *Background Mode:* Wakes up periodically (or via geofence trigger) to listen for 10 seconds.


* **Signal Processing:**
* Captures audio buffer via microphone.
* Performs FFT (Fast Fourier Transform) locally on the device.
* **Privacy Filter:** Discards all audio frequencies below 16kHz immediately (voice range). No audio is saved, only the binary payload.


* **Data Payload:** Sends `[Timestamp] + [Beacon_ID] + [Signal_Strength_dB]` to the Laravel API.

#### **Module C: The DMP (Data Management Platform)**

The central Laravel application that correlates data points.

* **Ingestion Engine:** A high-throughput API endpoint to receive SDK payloads.
* **Attribution Logic:**
* *Match Rule:* If `User_X` hears `Campaign_ID_101` (TV Ad) AND later hears `Location_ID_55` (Retail Store) within `7_Days` -> Record "Attribution Conversion".


* **Retargeting Segments:**
* Group users who heard `Location_ID_55` (Store) into a "Recent Visitors" audience list for export to Facebook/Google Ads.



---

### **5. User Stories**

* **Advertiser (Retailer):** "As a marketing manager, I want to upload my radio ad audio file and get a 'trackable' version back so I can see how many listeners actually visited my store."
* **Developer (Publisher):** "As an app developer, I want a drop-in SDK that doesn't drain my user's battery, so I can earn revenue by enabling tracking permissions."
* **Consumer:** "As a user, I want to be notified when I am in a store with a sale, and I want assurance that my private conversations are not being recorded."

---

### **6. Technical Requirements & Constraints**

#### **Performance**

* **Latency:** Signal detection on the phone must happen within 2.0 seconds of exposure.
* **Battery Impact:** The SDK must consume <3% of daily battery life. It should use accelerometer data to stop listening when the phone is stationary (e.g., sitting on a nightstand).

#### **Privacy & Compliance (Crucial)**

* **Hard Constraint:** iOS/Android visual microphone indicators (Orange Dot).
* **Requirement:** The SDK must support an "Explicit Opt-In" flow. The Demo App must demonstrate a value exchange (e.g., "Grant Mic permission to unlock this coupon").
* **Data Retention:** Raw signal logs are deleted after 30 days. Only aggregate attribution data is stored permanently.

---

### **7. MVP Development Roadmap**

#### **Sprint 1: The "Hello World" of Sound**

* **Goal:** Prove transmission reliability.
* **Task:** Build a Python script to generate a tone. Build a React Expo app that turns the screen Green when it hears the tone.
* **Success Metric:** Detection works at 15 feet distance with background conversation noise.

#### **Sprint 2: The Laravel Backend**

* **Goal:** Ingest data.
* **Task:** Set up Laravel API routes, database schema for `Beacons` and `Events`, and an authentication system for API keys.

#### **Sprint 3: The Dashboard (React)**

* **Goal:** Visualizing the data.
* **Task:** Create a frontend where a user can upload a file, get the watermark, and view a graph of "dummy" hits. Use OpenAI to generate "mock" analysis summaries for the UI.

#### **Sprint 4: The SDK Packaging**

* **Goal:** Distribution.
* **Task:** Refactor the detection code into a standalone library (`.aar` / `.framework`) that can be imported into *other* apps.

---

### **8. Future AI Integrations (Post-MVP)**

* **Contextual Anomaly Detection:** Use OpenAI to flag irregular traffic patterns (e.g., "Why did 500 signals fire at 3 AM?").
* **Predictive Footfall:** Use historical data to predict store traffic volume based on ad spend levels.