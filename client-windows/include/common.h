/**
 * GPU Share Client - Common Header
 * IMPORTANT: This header must be included first in all source files
 * to ensure correct header order for winsock2.h
 * Copyright (c) 2024 Xman Studio Thailand
 */

#ifndef COMMON_H
#define COMMON_H

#ifdef __cplusplus
extern "C" {
#endif

// Windows version targeting - must be before any Windows headers
#ifndef NTDDI_VERSION
#define NTDDI_VERSION 0x0A000000  // Windows 10
#endif
#ifndef _WIN32_WINNT
#define _WIN32_WINNT 0x0A00      // Windows 10
#endif
#ifndef WINVER
#define WINVER 0x0A00
#endif

// UNICODE support
#ifndef UNICODE
#define UNICODE
#endif
#ifndef _UNICODE
#define _UNICODE
#endif

// Suppress CRT warnings
#ifndef _CRT_SECURE_NO_WARNINGS
#define _CRT_SECURE_NO_WARNINGS
#endif

// IMPORTANT: winsock2.h MUST be included BEFORE windows.h
// This prevents redefinition errors with winsock.h
#include <winsock2.h>
#include <ws2tcpip.h>

// Now include windows.h
#include <windows.h>

// Standard C headers
#include <stdint.h>
#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#ifdef __cplusplus
}
#endif

#endif // COMMON_H
