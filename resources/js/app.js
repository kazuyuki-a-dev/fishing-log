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

// 単位変換ツール（#122）：どれか1つの欄に入れると、ほかの欄を計算して出す
// 入れた欄はそのまま残し、ほかの欄だけ書き換える。計算はブラウザの中だけで、何も送らない
const COEF_STORAGE_KEY = "fishinglog.unitConverter.coef";

// 小数を digits 桁に丸めて、後ろの 0 を消した文字にする（1.50 → "1.5"）
function roundText(value, digits) {
    return String(Number(value.toFixed(digits)));
}

// 数字として読めて 0 より大きいときだけ数にする。空や文字なら null
function toPositive(text) {
    const value = Number.parseFloat(text);
    return Number.isFinite(value) && value > 0 ? value : null;
}

// 号の小さい順の表 [[号, lb], ...] で、from 列の値から to 列の値を前後の行の割合で出す。表の外なら null
function interpolate(table, value, from, to) {
    for (let i = 0; i < table.length - 1; i++) {
        const [a, b] = [table[i], table[i + 1]];
        if (value >= a[from] && value <= b[from]) {
            const rate = (value - a[from]) / (b[from] - a[from]);
            return a[to] + (b[to] - a[to]) * rate;
        }
    }
    return null;
}

Alpine.data("unitConverter", (units) => {
    const types = units.line.types;
    const defaults = {};
    for (const [key, type] of Object.entries(types)) {
        if (type.coef) {
            defaults[key] = type.coef.default;
        }
    }

    // 前に変えた係数を読む。読めないとき（プライベートモードなど）や範囲の外の値は、はじめの値
    const coef = { ...defaults };
    try {
        const saved = JSON.parse(localStorage.getItem(COEF_STORAGE_KEY)) ?? {};
        for (const key of Object.keys(defaults)) {
            const value = Number(saved[key]);
            if (value >= types[key].coef.min && value <= types[key].coef.max) {
                coef[key] = value;
            }
        }
    } catch {
        // はじめの値のまま
    }

    const emptyOf = (group) =>
        Object.fromEntries(Object.keys(group).map((key) => [key, ""]));

    return {
        weight: emptyOf(units.weight),
        length: emptyOf(units.length),
        line: { gou: "", lb: "", kg: "" },
        lineType: "nylon",
        coef,
        // 最後に入れたラインの欄（種類や係数を変えたら、ここから計算し直す）
        lastLine: null,
        outOfTable: false,

        // 重さ・長さ：入れた値を基準の単位（g・cm）にしてから、ほかの単位に直す
        convert(groupName, from, text) {
            const group = units[groupName];
            const value = toPositive(text);
            for (const [key, unit] of Object.entries(group)) {
                if (key === from) {
                    continue;
                }
                this[groupName][key] =
                    value === null
                        ? ""
                        : roundText((value * group[from].value) / unit.value, unit.digits);
            }
        },

        // 号 → lb。ナイロン・フロロは表、PE・エステルは「号 × 係数」
        gouToLb(gou) {
            const type = types[this.lineType];
            if (type.table) {
                return interpolate(type.table, gou, 0, 1);
            }
            return gou * Number(this.coef[this.lineType]);
        },

        // lb → 号（上の逆）
        lbToGou(lb) {
            const type = types[this.lineType];
            if (type.table) {
                return interpolate(type.table, lb, 1, 0);
            }
            return lb / Number(this.coef[this.lineType]);
        },

        // ライン：入れた値を lb にしてから、ほかの欄に直す
        convertLine(from, text) {
            this.lastLine = { from, text };
            const value = toPositive(text);
            this.outOfTable = false;
            if (value === null) {
                for (const key of ["gou", "lb", "kg"]) {
                    if (key !== from) {
                        this.line[key] = "";
                    }
                }
                return;
            }

            const kgPerLb = units.line.kg_per_lb;
            let lb = value;
            if (from === "kg") {
                lb = value / kgPerLb;
            } else if (from === "gou") {
                lb = this.gouToLb(value);
            }

            // 号から lb が出せない（表の外）ときは、lb と kg を空にする
            if (lb === null) {
                this.outOfTable = true;
                this.line.lb = "";
                this.line.kg = "";
                return;
            }

            if (from !== "lb") {
                this.line.lb = roundText(lb, 2);
            }
            if (from !== "kg") {
                this.line.kg = roundText(lb * kgPerLb, 2);
            }
            if (from !== "gou") {
                const gou = this.lbToGou(lb);
                this.outOfTable = gou === null;
                this.line.gou = gou === null ? "" : roundText(gou, 2);
            }
        },

        // 最後に入れた欄から、もう一度計算する（種類や係数を変えたとき）
        recalcLine() {
            if (this.lastLine) {
                this.convertLine(this.lastLine.from, this.lastLine.text);
            }
        },

        setLineType(key) {
            this.lineType = key;
            this.recalcLine();
        },

        // 係数を変えたら、範囲の中のときだけ使って覚えておく
        setCoef(key, text) {
            const value = toPositive(text);
            const { min, max } = types[key].coef;
            if (value === null || value < min || value > max) {
                return;
            }
            this.coef[key] = value;
            try {
                localStorage.setItem(COEF_STORAGE_KEY, JSON.stringify(this.coef));
            } catch {
                // 覚えられなくても、計算はそのまま使える
            }
            this.recalcLine();
        },

        resetCoef(key) {
            this.setCoef(key, String(defaults[key]));
        },
    };
});

window.Alpine = Alpine;
Alpine.start();
