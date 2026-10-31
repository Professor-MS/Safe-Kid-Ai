# FamilyTrack – Find My Child Location App

An academic final-year BS project prototype for child safety and real-time location tracking.

---

## 1. System Architecture Overview

```
 [Flutter Mobile App] (Parent / Child Role)
       |
       +--> Firebase Authentication (Sign up, Sign in, ID Token)
       |
       +--> Firebase Realtime Database (Live Location Lat/Lng & Battery)
       |
       +--> Laravel Backend API (Bearer Token: Firebase JWT)
                 |
                 +--> kreait/laravel-firebase (JWT Verification Middleware)
                 +--> Local SQLite / MySQL Database (Profiles, Safe Zones, History)
                 +--> Geofencing & Transition Detection (Haversine Formula)
                 +--> Firebase Cloud Messaging (FCM Push Notifications)
```

---

## 2. Prerequisites

- **PHP**: 8.2+ (with `pdo_sqlite`, `curl`, `mbstring`, `openssl`, `sodium`, `fileinfo`, `xml`, `zip` extensions enabled)
- **Composer**: 2.x
- **Flutter SDK**: 3.47+ / Dart 3.13+
- **Android Studio** (or Android SDK command-line tools + JDK 17/21)
- **Firebase Project**: Auth (Email/Password), Realtime Database, Cloud Messaging enabled.

---

## 3. How to Run & Preview

### Step 1: Start Laravel Backend

Open a terminal and run:

```bash
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

> **Note on Network URLs:**
> - Android Emulator connects to host computer via: `http://10.0.2.2:8000/api`
> - Physical Android phone (connected to same Wi-Fi) connects via your PC LAN IP: `http://<YOUR_LAN_IP>:8000/api`

### Step 2: Run Mobile App (Flutter)

#### Option A: Running on a Physical Android Device (Recommended)
1. Enable **Developer Options** and **USB Debugging** on your Android phone.
2. Connect phone via USB cable to PC. Run `flutter devices` to verify device is detected.
3. Find your PC local IP (`ipconfig` on Windows, e.g., `192.168.1.5`).
4. Run:
```bash
cd mobile
flutter run --dart-define=BACKEND_BASE_URL=http://<YOUR_LAN_IP>:8000/api
```

#### Option B: Running on Android Studio Emulator
1. Start an Android Virtual Device (AVD) from Android Studio Device Manager.
2. Run:
```bash
cd mobile
flutter run
```
*(By default `AppConfig.baseUrl` uses `http://10.0.2.2:8000/api` which points directly to your PC backend from the Android emulator).*

---

## 4. Running Two App Instances (Parent & Child)

To test the multi-user workflow on your machine:
1. **Device 1 (Parent)**: Run on an Android Emulator or Physical Phone #1.
   - Tap **Create Account** -> select **Parent** role.
   - Log in -> Navigates to **Parent Dashboard** (`/parent-home`).
2. **Device 2 (Child)**: Run on Physical Phone #2 (or a second AVD emulator instance: `flutter run -d <emulator-id>`).
   - Tap **Create Account** -> select **Child** role.
   - Log in -> Navigates to **Child Dashboard** (`/child-home`), featuring the prominent active sharing indicator.

---

## 5. Automated Tests

### Backend Unit & Feature Tests
```bash
cd backend
php artisan test
```
*Validates token verification, unauthorized access rejection (401), immutable role protection (409), cross-user data isolation, and device FCM token upserts.*

### Flutter Static Analysis & Widget Tests
```bash
cd mobile
flutter analyze
flutter test
```
*Validates Dart strict analysis and UI widget rendering.*

---

## 6. Milestones Completed

- **Milestone 0**: Monorepo scaffolding, environment configuration, dependency management, Firebase credentials setup.
- **Milestone 1**: Database schema & migrations, Firebase token verification middleware, Auth & User REST APIs, Flutter Auth UI (Role selection, Sign up, Sign in, Parent & Child homes), token auto-refresh interceptor, FCM token registration.

