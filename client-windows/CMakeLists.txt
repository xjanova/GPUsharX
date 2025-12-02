cmake_minimum_required(VERSION 3.15)
project(GPUShareClient VERSION 1.0.0 LANGUAGES C)

# Set C standard
set(CMAKE_C_STANDARD 11)
set(CMAKE_C_STANDARD_REQUIRED ON)

# Windows specific settings
if(WIN32)
    set(CMAKE_WIN32_EXECUTABLE ON)
    add_definitions(-DUNICODE -D_UNICODE -DWIN32_LEAN_AND_MEAN)
endif()

# Source files
set(SOURCES
    src/main.c
    src/gpu_monitor.c
    src/system_monitor.c
    src/api_client.c
)

# Create executable
add_executable(${PROJECT_NAME} WIN32 ${SOURCES})

# Include directories
target_include_directories(${PROJECT_NAME} PRIVATE ${CMAKE_SOURCE_DIR}/include)

# Link libraries
target_link_libraries(${PROJECT_NAME}
    user32
    gdi32
    shell32
    comctl32
    psapi
    iphlpapi
    winhttp
)

# Set output name
set_target_properties(${PROJECT_NAME} PROPERTIES
    OUTPUT_NAME "GPUShareClient"
    RUNTIME_OUTPUT_DIRECTORY "${CMAKE_BINARY_DIR}/bin"
)

# Enable optimizations for Release
if(CMAKE_BUILD_TYPE STREQUAL "Release")
    target_compile_options(${PROJECT_NAME} PRIVATE /O2 /GL)
    target_link_options(${PROJECT_NAME} PRIVATE /LTCG)
endif()
