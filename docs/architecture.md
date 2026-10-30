# FamilyTrack Architecture & Design

## Overview
FamilyTrack is a mobile child-safety application allowing a parent to monitor a child's smartphone location in real time with geofencing, safe zones, battery status, and SOS alerts.

## Technology Stack
- **Mobile**: Flutter 3.47+ / Dart 3.13+ (Android first, iOS-compatible codebase)
- **Backend**: Laravel 11 REST API (PHP 8.3)
- **Database**: SQLite (Local development), MySQL-compatible schema
- **Identity & Auth**: Firebase Authentication (Email/Password) + Bearer ID Token verification in Laravel
- **Live Location**: Firebase Realtime Database
- **Push Notifications**: Firebase Cloud Messaging (FCM)
- **Maps**: Google Maps Platform (`google_maps_flutter`)

## Core Flow
1. **User Sign Up / Login**: Performed via Firebase Auth on Flutter.
2. **Profile Sync**: Flutter retrieves Firebase ID Token and calls Laravel `POST /api/auth/register-profile` to persist role (`parent` | `child`) and name. Role is immutable.
3. **Authentication**: All API requests pass `Authorization: Bearer <Firebase_ID_Token>`, verified by Laravel `VerifyFirebaseToken` middleware.
4. **Live Location Tracking**: Child app updates Realtime Database node `/locations/{child_id}` with lat, lng, accuracy, battery, and timestamp. Parent listens reactively.
5. **Location History & Geofencing**: Child app batches location points to Laravel `POST /api/tracking/batch` for historical logging and server-side geofence boundary checks (Haversine formula).

