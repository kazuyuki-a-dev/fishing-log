/**
 * 写真の Exif から、撮影日時と位置を読み取る（読めないものは null）
 * 撮影日時は、釣行日時の欄（datetime-local）に入れられる形「2026-09-20T06:12」で返す
 */
export async function readPhotoHints(file) {
    try {
        const { default: ExifReader } = await import("exifreader");
        const tags = await ExifReader.load(file, { expanded: true });

        // 撮影日時は「2026:09:20 06:12:34」の形で入っている
        const raw = tags.exif?.DateTimeOriginal?.description ?? "";
        const matched = raw.match(/^(\d{4}):(\d{2}):(\d{2}) (\d{2}):(\d{2})/);
        const takenAt = matched
            ? `${matched[1]}-${matched[2]}-${matched[3]}T${matched[4]}:${matched[5]}`
            : null;

        // 位置。0,0（大西洋の真ん中）は「入っていない」とみなす
        const lat = tags.gps?.Latitude;
        const lng = tags.gps?.Longitude;
        const hasLocation =
            typeof lat === "number" &&
            typeof lng === "number" &&
            !(lat === 0 && lng === 0);

        return {
            takenAt,
            lat: hasLocation ? lat : null,
            lng: hasLocation ? lng : null,
        };
    } catch (error) {
        console.error("Exif を読み取れませんでした", error);
        return { takenAt: null, lat: null, lng: null };
    }
}

/**
 * HEIC（iPhone の写真）かどうか。パソコンでは種類が空のことがあるので、ファイル名も見る
 */
export function isHeicFile(file) {
    return /image\/hei[cf]/i.test(file.type) || /\.hei[cf]$/i.test(file.name);
}

/**
 * HEIC を JPEG に変換する。変換の部品は大きいので、ここで初めて読み込む
 */
export async function convertHeicToJpeg(file) {
    const { heicTo } = await import("heic-to");
    const blob = await heicTo({ blob: file, type: "image/jpeg", quality: 0.9 });

    return new File([blob], file.name.replace(/\.hei[cf]$/i, ".jpg"), {
        type: "image/jpeg",
    });
}

/**
 * 近くの釣り場を探す（「この釣り場ですか？」と同じ窓口）
 */
export async function findNearbySpots(url, lat, lng) {
    try {
        const params = new URLSearchParams({ lat, lng });
        const response = await fetch(`${url}?${params}`, {
            headers: { Accept: "application/json" },
        });

        return response.ok ? (await response.json()).spots : [];
    } catch {
        return [];
    }
}
