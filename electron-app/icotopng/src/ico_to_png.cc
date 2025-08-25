#include <napi.h>
#include <vector>
#include <fstream>
#include <cstring>
#include "lodepng.h"
#include <math.h>
#define NANOSVG_IMPLEMENTATION	// Expands implementation
#define NANOSVGRAST_IMPLEMENTATION
#define NANOSVGRAST_CPLUSPLUS
#include "nanosvg.c"
#include "nanosvgrast.c"

#pragma pack(push, 1)
typedef struct {
    uint16_t reserved;
    uint16_t type;
    uint16_t count;
} IcoHeader;

typedef struct {
    uint8_t width;
    uint8_t height;
    uint8_t color_count;
    uint8_t reserved;
    uint16_t planes;
    uint16_t bit_count;
    uint32_t size_in_bytes;
    uint32_t offset;
} IcoDirEntry;

typedef struct {
    uint32_t size;
    int32_t width;
    int32_t height;
    uint16_t planes;
    uint16_t bit_count;
    uint32_t compression;
    uint32_t image_size;
    int32_t x_pixels_per_meter;
    int32_t y_pixels_per_meter;
    uint32_t colors_used;
    uint32_t colors_important;
} BitmapInfoHeader;
#pragma pack(pop)

// Helper: BGRA to RGBA
void bgraToRgba(uint8_t* bgra, uint8_t* rgba, int pixelCount) {
    for (int i = 0; i < pixelCount; ++i) {
        rgba[i * 4 + 0] = bgra[i * 4 + 2]; // R
        rgba[i * 4 + 1] = bgra[i * 4 + 1]; // G
        rgba[i * 4 + 2] = bgra[i * 4 + 0]; // B
        rgba[i * 4 + 3] = bgra[i * 4 + 3]; // A
    }
}

// Helper: BGR to RGBA (alpha = 255)
void bgrToRgba(uint8_t* bgr, uint8_t* rgba, int pixelCount) {
    for (int i = 0; i < pixelCount; ++i) {
        rgba[i * 4 + 0] = bgr[i * 3 + 2]; // R
        rgba[i * 4 + 1] = bgr[i * 3 + 1]; // G
        rgba[i * 4 + 2] = bgr[i * 3 + 0]; // B
        rgba[i * 4 + 3] = 255;            // A
    }
}

// Helper: 8-bit indexed to RGBA using palette
void indexedToRgba(uint8_t* indexed, uint8_t* palette, uint8_t* rgba, int pixelCount) {
    for (int i = 0; i < pixelCount; ++i) {
        uint8_t idx = indexed[i];
        rgba[i * 4 + 0] = palette[idx * 4 + 2]; // R
        rgba[i * 4 + 1] = palette[idx * 4 + 1]; // G
        rgba[i * 4 + 2] = palette[idx * 4 + 0]; // B
        rgba[i * 4 + 3] = palette[idx * 4 + 3]; // A
    }
}

// Helper: 4-bit indexed to RGBA using palette
void fourBitToRgba(uint8_t* fourBit, uint8_t* palette, uint8_t* rgba, int width, int height, int stride) {
    for (int y = 0; y < height; ++y) {
        uint8_t* srcRow = &fourBit[(height - 1 - y) * stride];
        uint8_t* dstRow = &rgba[y * width * 4];

        for (int x = 0; x < width; ++x) {
            int byteIndex = x / 2;
            bool isHighNibble = (x % 2 == 0);
            uint8_t nibble = isHighNibble ? (srcRow[byteIndex] >> 4) : (srcRow[byteIndex] & 0x0F);

            dstRow[x * 4 + 0] = palette[nibble * 4 + 2]; // R
            dstRow[x * 4 + 1] = palette[nibble * 4 + 1]; // G
            dstRow[x * 4 + 2] = palette[nibble * 4 + 0]; // B
            dstRow[x * 4 + 3] = palette[nibble * 4 + 3]; // A
        }
    }
}

// Helper: 1-bit indexed to RGBA using palette
void oneBitToRgba(uint8_t* oneBit, uint8_t* palette, uint8_t* rgba, int width, int height, int stride) {
    for (int y = 0; y < height; ++y) {
        uint8_t* srcRow = &oneBit[(height - 1 - y) * stride];
        uint8_t* dstRow = &rgba[y * width * 4];

        for (int x = 0; x < width; ++x) {
            int byteIndex = x / 8;
            int bitIndex = 7 - (x % 8); // MSB first
            uint8_t bit = (srcRow[byteIndex] >> bitIndex) & 1;

            dstRow[x * 4 + 0] = palette[bit * 4 + 2]; // R
            dstRow[x * 4 + 1] = palette[bit * 4 + 1]; // G
            dstRow[x * 4 + 2] = palette[bit * 4 + 0]; // B
            dstRow[x * 4 + 3] = palette[bit * 4 + 3]; // A
        }
    }
}
Napi::Boolean ConvertSvgToPng(const Napi::CallbackInfo& info) {
    Napi::Env env = info.Env();

    if (info.Length() < 3) {
        Napi::TypeError::New(env, "Expected 3 arguments: inputPath, outputPath, size").ThrowAsJavaScriptException();
        return Napi::Boolean::New(env, false);
    }

    if (!info[0].IsString() || !info[1].IsString() || !info[2].IsNumber()) {
        Napi::TypeError::New(env, "Arguments must be: string, string, number").ThrowAsJavaScriptException();
        return Napi::Boolean::New(env, false);
    }

    std::string inputPath = info[0].As<Napi::String>().Utf8Value();
    std::string outputPath = info[1].As<Napi::String>().Utf8Value();
    int size = info[2].As<Napi::Number>().Int32Value();

    NSVGimage* image = nsvgParseFromFile(inputPath.c_str(), "px", 96);
    if (!image) {
        return Napi::Boolean::New(env, false);
    }

    NSVGrasterizer* rast = nsvgCreateRasterizer();
    if (!rast) {
        nsvgDelete(image);
        return Napi::Boolean::New(env, false);
    }

    unsigned char* img = new unsigned char[size * size * 4];
    nsvgRasterize(rast, image, 0, 0, 1, img, size, size, size * 4);

    unsigned error = lodepng::encode(outputPath, img, size, size, LodePNGColorType::LCT_RGBA, 8);
    delete[] img;
    nsvgDeleteRasterizer(rast);
    nsvgDelete(image);

    if (error) {
        return Napi::Boolean::New(env, false);
    }

    return Napi::Boolean::New(env, true);
}
// Main conversion function
Napi::Boolean ConvertIcoToPng(const Napi::CallbackInfo &info) {
    Napi::Env env = info.Env();
    printf("ICO Image Info:\n");
    if (info.Length() < 2 || !info[0].IsString() || !info[1].IsString()) {
        Napi::TypeError::New(env, "Expected two string arguments: inputPath and outputPath").ThrowAsJavaScriptException();
        return Napi::Boolean::New(env, false);
    }

    std::string inputPath = info[0].As<Napi::String>();
    std::string outputPath = info[1].As<Napi::String>();

    std::ifstream file(inputPath, std::ios::binary);
    if (!file) {
        printf("file error\n");
        return Napi::Boolean::New(env, false);
    }

    // Read ICO header
    IcoHeader header;
    file.read(reinterpret_cast<char*>(&header), sizeof(header));
    if (header.type != 1 || header.count == 0) {
        printf("Invalid ICO header: type=%d, count=%d\n", header.type, header.count);
        return Napi::Boolean::New(env, false);
    }

    // Read all directory entries
    std::vector<IcoDirEntry> entries(header.count);
    file.read(reinterpret_cast<char*>(entries.data()), sizeof(IcoDirEntry) * header.count);

    // Find the largest icon (by area), or just take the first if only one
    int bestIndex = 0;
    if (header.count > 1) {
        int maxArea = 0;
        for (int i = 0; i < header.count; ++i) {
            int area = entries[i].width * entries[i].height;
            if (area > maxArea) {
                maxArea = area;
                bestIndex = i;
            }
        }
    }

    IcoDirEntry &entry = entries[bestIndex];
    file.seekg(entry.offset);
    printf("Selected icon: width=%d, height=%d, bit_count=%d\n", entry.width, entry.height, entry.bit_count);

    // Read BITMAPINFOHEADER
    BitmapInfoHeader bmpHeader;
    file.read(reinterpret_cast<char*>(&bmpHeader), sizeof(bmpHeader));

    int width = bmpHeader.width;
    int height = bmpHeader.height / 2; // ICO includes XOR mask + AND mask
    int bitCount = bmpHeader.bit_count;
    printf("BMP Header: width=%d, height=%d, bit_count=%d\n", bmpHeader.width, bmpHeader.height, bmpHeader.bit_count);

    // Read palette if 8-bit, 4-bit, or 1-bit
    std::vector<uint8_t> palette;
    if (bitCount == 8) {
        int paletteSize = 256 * 4;
        palette.resize(paletteSize);
        file.read(reinterpret_cast<char*>(palette.data()), paletteSize);
    } else if (bitCount == 4) {
        int paletteSize = 16 * 4; // 16 colors, 4 bytes each (BGRA)
        palette.resize(paletteSize);
        file.read(reinterpret_cast<char*>(palette.data()), paletteSize);
    } else if (bitCount == 1) {
        int paletteSize = 2 * 4; // 2 colors, 4 bytes each (BGRA)
        palette.resize(paletteSize);
        file.read(reinterpret_cast<char*>(palette.data()), paletteSize);
    }

    // Calculate row stride (with 4-byte alignment)
    int bytesPerPixel = (bitCount == 32) ? 4 : (bitCount == 24) ? 3 : (bitCount == 8) ? 1 : (bitCount == 4) ? 1 : 1;
    int stride = (width * bytesPerPixel + 3) & ~3;

    // Read pixel data (XOR mask)
    std::vector<uint8_t> pixelData(stride * height);
    file.read(reinterpret_cast<char*>(pixelData.data()), pixelData.size());

    // Convert to RGBA
    std::vector<uint8_t> rgba(width * height * 4);

    if (bitCount == 32) {
        for (int y = 0; y < height; ++y) {
            uint8_t* srcRow = &pixelData[(height - 1 - y) * stride];
            uint8_t* dstRow = &rgba[y * width * 4];
            bgraToRgba(srcRow, dstRow, width);
        }
    } else if (bitCount == 24) {
        for (int y = 0; y < height; ++y) {
            uint8_t* srcRow = &pixelData[(height - 1 - y) * stride];
            uint8_t* dstRow = &rgba[y * width * 4];
            bgrToRgba(srcRow, dstRow, width);
        }
    } else if (bitCount == 8) {
        for (int y = 0; y < height; ++y) {
            uint8_t* srcRow = &pixelData[(height - 1 - y) * stride];
            uint8_t* dstRow = &rgba[y * width * 4];
            indexedToRgba(srcRow, palette.data(), dstRow, width);
        }
    } else if (bitCount == 4) {
        fourBitToRgba(pixelData.data(), palette.data(), rgba.data(), width, height, stride);
    } else if (bitCount == 1) {
        oneBitToRgba(pixelData.data(), palette.data(), rgba.data(), width, height, stride);
    } else {
        printf("Unsupported format ICO bitCount:%d\n", bitCount);
        return Napi::Boolean::New(env, false); // Unsupported format
    }

    // Encode to PNG
    unsigned error = lodepng::encode(outputPath, rgba, width, height);
    if (error) {
        printf("Encode to PNG error \n");
        return Napi::Boolean::New(env, false);
    }

    return Napi::Boolean::New(env, true);
}

// Detect file type: return -1 (unknown/error), 0 (ICO), 1 (PNG), 2 (SVG)
Napi::Number DetectFileType(const Napi::CallbackInfo &info) {
    Napi::Env env = info.Env();

    if (info.Length() < 1 || !info[0].IsString()) {
        Napi::TypeError::New(env, "Expected one string argument: filePath").ThrowAsJavaScriptException();
        return Napi::Number::New(env, -1);
    }

    std::string filePath = info[0].As<Napi::String>();
    std::ifstream file(filePath, std::ios::binary);
    if (!file) {
        return Napi::Number::New(env, -1);
    }

    // Read first 8 bytes to check ICO/PNG
    uint8_t header[8];
    file.read(reinterpret_cast<char*>(header), 8);
    if (!file) {
        return Napi::Number::New(env, -1);
    }

    // Check ICO: 00 00 01 00
    if (header[0] == 0x00 && header[1] == 0x00 && header[2] == 0x01 && header[3] == 0x00) {
        return Napi::Number::New(env, 0);
    }

    // Check PNG: 89 50 4E 47
    if (header[0] == 0x89 && header[1] == 0x50 && header[2] == 0x4E && header[3] == 0x47) {
        return Napi::Number::New(env, 1);
    }

    // If not ICO or PNG, check if it's SVG (text file starting with <svg)
    file.seekg(0, std::ios::beg);
    std::vector<char> buffer(1024);
    file.read(buffer.data(), buffer.size());
    buffer[file.gcount()] = '\0'; // Ensure null-terminated

    std::string content(buffer.data());
    if (content.find("<svg") != std::string::npos) {
        return Napi::Number::New(env, 2);
    }

    // Unknown format
    return Napi::Number::New(env, -1);
}

// Module init
Napi::Object Init(Napi::Env env, Napi::Object exports) {
    exports.Set(Napi::String::New(env, "convertIcoToPng"), Napi::Function::New(env, ConvertIcoToPng));
    exports.Set(Napi::String::New(env, "detectFileType"), Napi::Function::New(env, DetectFileType));
	exports.Set(Napi::String::New(env, "convertSvgToPng"), Napi::Function::New(env, ConvertSvgToPng));
    return exports;
}

NODE_API_MODULE(ico_to_png, Init)
