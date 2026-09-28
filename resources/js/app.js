
import Alpine from "alpinejs";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

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
Alpine.data("spotMapInput", (lat, lng) => {
    // Leaflet の地図は Alpine の外（ふつうの変数）で持つ。Alpine に入れると動かなくなるため
    let map = null;
    let pin = null;

    return {
        lat: lat,
        lng: lng,
        message: "",

        init() {
            const hasLocation = this.lat !== null && this.lat !== "";
            map = baseMap(
                this.$refs.map,
                hasLocation ? [this.lat, this.lng] : [36.5, 138.0],
                hasLocation ? 15 : 5,
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
            if (pin) {
                pin.remove();
                pin = null;
            }
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

window.Alpine = Alpine;
Alpine.start();
