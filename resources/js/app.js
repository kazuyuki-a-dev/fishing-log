import Alpine from "alpinejs";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import {
    readPhotoHints,
    isHeicFile,
    convertHeicToJpeg,
    findNearbySpots,
} from "./photo-hints";

// 地図の土台（OpenStreetMap）を置く。右下の「© OpenStreetMap」は使うときの決まり
function baseMap(element, center, zoom) {
    const map = L.map(element).setView(center, zoom);
    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);
    return map;
}

const pinStyle = {
    radius: 8,
    color: "#C2461F",
    fillColor: "#E0572B",
    fillOpacity: 0.9,
};

// 釣り場の登録・編集画面：タップでピンを置く
Alpine.data("spotMapInput", (lat, lng, nearbyUrl = null, center = null) => {
    // Leaflet の地図は Alpine の外（ふつうの変数）で持つ。Alpine に入れると動かなくなるため
    let map = null;
    let pin = null;

    return {
        lat: lat,
        lng: lng,
        message: "",
        nearby: [],

        init() {
            const hasLocation = this.lat !== null && this.lat !== "";
            map = baseMap(
                this.$refs.map,
                hasLocation ? [this.lat, this.lng] : (center ?? [36.5, 138.0]),
                hasLocation ? 15 : center ? 11 : 5,
            );
            if (hasLocation) {
                this.place(Number(this.lat), Number(this.lng));
            }
            map.on("click", (event) =>
                this.place(event.latlng.lat, event.latlng.lng),
            );
        },

        place(lat, lng) {
            this.lat = lat.toFixed(7);
            this.lng = lng.toFixed(7);
            if (pin) {
                pin.setLatLng([lat, lng]);
            } else {
                pin = L.circleMarker([lat, lng], pinStyle).addTo(map);
            }
            this.findNearby();
        },

        useCurrentLocation() {
            if (!navigator.geolocation) {
                this.message = "この端末では現在地を使えません。";
                return;
            }
            this.message = "現在地を調べています…";
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.place(
                        position.coords.latitude,
                        position.coords.longitude,
                    );
                    map.setView(
                        [position.coords.latitude, position.coords.longitude],
                        16,
                    );
                    this.message =
                        "現在地にピンを置きました。ずれていたら地図をタップして直してください。";
                },
                () => {
                    this.message =
                        "現在地を取得できませんでした。地図をタップして位置を決めてください。";
                },
            );
        },

        clear() {
            this.lat = "";
            this.lng = "";
            this.nearby = [];
            if (pin) {
                pin.remove();
                pin = null;
            }
        },

        async findNearby() {
            // 編集画面では提案しない（nearbyUrl が渡されない）
            if (!nearbyUrl) {
                return;
            }
            const params = new URLSearchParams({
                lat: this.lat,
                lng: this.lng,
            });
            try {
                const response = await fetch(`${nearbyUrl}?${params}`, {
                    headers: { Accept: "application/json" },
                });
                this.nearby = response.ok ? (await response.json()).spots : [];
            } catch {
                this.nearby = [];
            }
        },

        async readLocationFrom(event) {
            const file = event.target.files[0];
            if (!file) {
                return;
            }
            const hints = await readPhotoHints(file);
            // この写真は位置を読むためだけに使う。選んだままにしない
            event.target.value = "";

            if (hints.lat === null) {
                this.message =
                    "この写真には位置が入っていません。地図をタップするか「現在地を使う」で位置を決めてください。";
                return;
            }
            this.place(hints.lat, hints.lng);
            map.setView([hints.lat, hints.lng], 16);
            this.message =
                "写真を撮った場所にピンを置きました。ずれていたら地図をタップして直してください。";
        },
    };
});

// 釣り場カルテ：本人には正確な位置のピン、ほかの人には「このあたり」の四角
Alpine.data("spotMapView", (location) => ({
    init() {
        if (location.exact) {
            const map = baseMap(
                this.$refs.map,
                [location.lat, location.lng],
                15,
            );
            L.circleMarker([location.lat, location.lng], pinStyle).addTo(map);
        } else {
            const area = [
                [location.lat, location.lng],
                [location.lat + 0.01, location.lng + 0.01],
            ];
            const map = baseMap(
                this.$refs.map,
                [location.lat, location.lng],
                13,
            );
            L.rectangle(area, {
                color: "#12465A",
                weight: 2,
                fillOpacity: 0.15,
            }).addTo(map);
            map.fitBounds(area, { padding: [20, 20] });
        }
    },
}));

// 釣行の登録・編集画面：釣果の行と、写真から読み取った候補
Alpine.data("catchRows", (initialRows, nearbyUrl) => ({
    rows: initialRows.map((row, n) => ({ ...row, key: n })),
    nextKey: 1000,
    hint: null, // 写真から読み取った候補 { takenAt, spots }
    photoMessage: "",
    converting: false,

    add() {
        this.rows.push({ fish_species: "", method: "", key: this.nextKey++ });
    },

    remove(i) {
        this.rows.splice(i, 1);
    },

    async pickPhoto(event) {
        const input = event.target;
        const file = input.files[0];
        this.photoMessage = "";
        if (!file) {
            return;
        }

        // Exif は、HEIC を変換する前に読む（変換すると消えるため）
        const hints = await readPhotoHints(file);

        if (isHeicFile(file)) {
            this.converting = true;
            try {
                const jpeg = await convertHeicToJpeg(file);
                // 選ばれたファイルを、変換した JPEG に差し替える
                const transfer = new DataTransfer();
                transfer.items.add(jpeg);
                input.files = transfer.files;
            } catch {
                input.value = "";
                this.photoMessage =
                    "この写真は変換できませんでした。JPEG で保存し直すか、別の写真を選んでください。";
                return;
            } finally {
                this.converting = false;
            }
        }

        if (!hints.takenAt && hints.lat === null) {
            this.photoMessage = "この写真には撮影日時や位置が入っていません。";
            return;
        }

        // 近くの釣り場のうち、釣り場の選択欄にあるものだけを候補にする
        let spots = [];
        if (hints.lat !== null) {
            spots = (
                await findNearbySpots(nearbyUrl, hints.lat, hints.lng)
            ).filter((spot) =>
                document.querySelector(`#spot_id option[value="${spot.id}"]`),
            );
        }
        this.hint = { takenAt: hints.takenAt, spots };
    },

    useTakenAt() {
        document.getElementById("went_at").value = this.hint.takenAt;
        this.photoMessage = "釣行日時を、写真の撮影日時にしました。";
    },

    useSpot(id) {
        document.getElementById("spot_id").value = String(id);
        this.photoMessage = "釣り場を選びました。";
    },
}));

// 過去の釣行のまとめて登録：写真1枚につき1行（PG15）
function emptyBulkRow() {
    return {
        went_at: "",
        spot_id: "",
        time_of_day: "",
        fish_species: "",
        fish_species_other: "",
        method: "",
        length_cm: "",
        preview: "",
        spotNote: "",
    };
}

Alpine.data("bulkRows", (initialRows, nearbyUrl, spotIds, maxRows) => {
    // 選んだ写真のファイルは Alpine の外で持つ（Alpine の中に入れると、ファイルとして送れなくなるため）
    const files = new Map();

    return {
        rows: initialRows.map((row, n) => ({
            ...emptyBulkRow(),
            ...row,
            key: n,
        })),
        nextKey: 1000,
        message: "",
        converting: false,

        // 行を足して、足した行を返す（いっぱいなら null）
        add(values = {}) {
            if (this.rows.length >= maxRows) {
                this.message = `1回に登録できるのは${maxRows}行までです。残りは保存したあとにもう一度選んでください。`;
                return null;
            }
            this.rows.push({
                ...emptyBulkRow(),
                ...values,
                key: this.nextKey++,
            });
            // push したあとの行を返す（こちらを書き換えると画面に反映される）
            return this.rows[this.rows.length - 1];
        },

        remove(i) {
            const row = this.rows[i];
            files.delete(row.key);
            if (row.preview) {
                URL.revokeObjectURL(row.preview);
            }
            this.rows.splice(i, 1);
        },

        async pickPhotos(event) {
            const picked = [...event.target.files];
            // 選んだ写真は各行に移すので、ここには残さない
            event.target.value = "";
            this.message = "";
            let added = 0;
            let skipped = 0;

            for (const original of picked) {
                // Exif は、HEIC を変換する前に読む（変換すると消えるため）
                this.converting = true;
                const hints = await readPhotoHints(original);
                let file = original;
                if (isHeicFile(original)) {
                    try {
                        file = await convertHeicToJpeg(original);
                    } catch {
                        skipped++;
                        this.converting = false;
                        continue;
                    }
                }
                this.converting = false;

                const row = this.add({
                    went_at: hints.takenAt ?? "",
                    preview: URL.createObjectURL(file),
                });
                if (!row) {
                    break;
                }
                files.set(row.key, file);
                added++;

                // 写真の位置の近くに、選べる釣り場があれば最初から選んでおく
                if (hints.lat !== null) {
                    const spot = (
                        await findNearbySpots(nearbyUrl, hints.lat, hints.lng)
                    ).find((candidate) => spotIds.includes(candidate.id));
                    if (spot) {
                        row.spot_id = String(spot.id);
                        row.spotNote = `写真の位置から「${spot.name}」（約 ${spot.distance}m）を選びました。違っていたら選び直してください。`;
                    }
                }
            }

            if (this.message === "") {
                this.message = `${added}枚の写真から行を作りました。空欄を埋めてください。`;
                if (skipped > 0) {
                    this.message += `（${skipped}枚は変換できなかったので飛ばしました）`;
                }
            }
        },

        // 行の写真欄に、選んだ写真を入れる（行が画面に出たときに呼ばれる）
        attachFile(input, key) {
            const file = files.get(key);
            if (!file) {
                return;
            }
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
        },
    };
});

window.Alpine = Alpine;
Alpine.start();
