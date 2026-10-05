


document.addEventListener("DOMContentLoaded", function () {
    const departmentSelect = document.getElementById('departmentSelect');
    let departmentId;

    // --- Set default dropdown
    const monthSelect = document.getElementById("monthSelect");
    const yearSelect = document.getElementById("yearSelect");
    const currentMonth = String((new Date()).getMonth() + 1).padStart(2, '0');
    const currentYear = String((new Date()).getFullYear());
    if (monthSelect && !monthSelect.value) {
        monthSelect.value = currentMonth;
    }
    if (yearSelect) yearSelect.value = currentYear;


    if (typeof isAdmin !== 'undefined' && isAdmin && departmentSelect) {
        departmentSelect.addEventListener('change', function () {
            departmentId = this.value;
            reloadGraphs();
        });
    }

    ['monthSelect', 'yearSelect'].forEach(id => {
        document.getElementById(id).addEventListener('change', reloadGraphs);
    });

    function reloadGraphs() {
        if (activeTab === 'check') {
            showCheckJobGraphs();
        } else if (activeTab === 'export') {
            showExportJobGraphs();
        } else if (activeTab === 'worklate') {
            showworklate();
        } else if (activeTab === 'IT') {
            showworklate();
        }
    }


});



// =====================================================================
// ✅ ชุดสีกราฟของแดชบอร์ด (8 hue ที่ผ่านการตรวจสอบ CVD/contrast แล้ว - ดู dataviz skill)
//    ลำดับคงที่เสมอ (ห้ามสลับ) เพราะลำดับคือกลไกความปลอดภัยของสี
// =====================================================================
const CHART_HUE = {
    blue: '#2a78d6',
    orange: '#eb6834',
    aqua: '#1baf7a',
    yellow: '#eda100',
    magenta: '#e87ba4',
    green: '#008300',
    violet: '#4a3aa7',
    red: '#e34948',
    gray: '#9a9890' // สีเป็นกลางสำหรับ "อื่นๆ"/ส่วนเกิน ไม่ใช่ 1 ใน 8 hue หลัก
};

// สถานะ (fixed scale: ดี -> วิกฤต) ใช้กับกราฟ "เกินกำหนด/ไม่เกินกำหนด" เท่านั้น
const STATUS_GOOD = '#0ca30c';
const STATUS_CRITICAL = '#d03b3b';

// โทนตัวอักษร/เส้นกริดของกราฟ (recessive - ไม่แย่งความสนใจจากข้อมูล)
const CHART_INK = { text: '#52514e', muted: '#898781', grid: '#e1e0d9' };

// แผนที่ "ชื่อสถานะงาน -> สี" ตายตัว (ใช้ลำดับ 8 hue เดิมเสมอ) เพื่อให้สถานะเดียวกัน
// เป็นสีเดียวกันทุกกราฟ/ทุกแท็บ ไม่สลับสีไปมาเวลาตัวกรองเปลี่ยน
// โทนสดใสแบบพาย 3D ตัวอย่าง (ม่วง ชมพู ส้ม เหลือง เขียว ฟ้า) สำหรับสถานะงาน
const JOB_STATUS_PASTEL = {
    blue: '#3b6ff5',
    orange: '#ff6a1a',
    aqua: '#22d3e0',
    yellow: '#eadc1c',
    magenta: '#e0288a',
    green: '#2fcf3a',
    violet: '#b012c8',
    red: '#ff3b3b'
};
const JOB_STATUS_COLOR = {
    'งานเข้าใหม่': JOB_STATUS_PASTEL.blue,
    'รอดำเนินการ': JOB_STATUS_PASTEL.orange,
    'กำลังดำเนินการ': JOB_STATUS_PASTEL.aqua,
    'รออนุมัติรับงาน': JOB_STATUS_PASTEL.yellow,
    'รออนุมัติส่งงาน': JOB_STATUS_PASTEL.magenta,
    'ปิดงาน': JOB_STATUS_PASTEL.green,
    'สำเร็จ (รอปิดงาน)': JOB_STATUS_PASTEL.violet,
    'ไม่อนุมัติ': JOB_STATUS_PASTEL.red
};
// ลำดับการวาด (คงลำดับ 8 hue ที่ผ่านการตรวจ adjacency ไว้)
const JOB_STATUS_ORDER = [
    'งานเข้าใหม่', 'รอดำเนินการ', 'กำลังดำเนินการ', 'รออนุมัติรับงาน',
    'รออนุมัติส่งงาน', 'ปิดงาน', 'สำเร็จ (รอปิดงาน)', 'ไม่อนุมัติ'
];

// รวมค่าของหลาย array แบบ element-wise (ใช้พับสถานะ "ไม่อนุมัติ" ย่อยๆ หลายแบบ
// ให้เหลือเส้น/ชิ้นเดียวในกราฟ แทนที่จะมีหลายชิ้นสีเดียวกันวนซ้ำจนรก)
function sumArrays(...arrays) {
    const len = Math.max(0, ...arrays.map(a => (Array.isArray(a) ? a.length : 0)));
    const out = new Array(len).fill(0);
    arrays.forEach(arr => {
        if (!Array.isArray(arr)) return;
        arr.forEach((v, i) => { out[i] += Number(v) || 0; });
    });
    return out;
}

// ✅ Plugin สำหรับแสดงข้อความตรงกลางกราฟโดนัท (ตัวเลขรวมแบบ hero-figure สั้นๆ)
// ยึดขนาด/ตำแหน่งจากรู "โดนัทจริง" (arc.innerRadius/x/y) ไม่ใช่ width/height ของทั้ง canvas
// แล้ววัดความกว้างตัวอักษรก่อนวาดเสมอ (measure first) กันข้อความล้นรูตอนกราฟถูกบีบแคบ/สูง
Chart.register({
    id: 'centerText',
    beforeDraw: function (chart) {
        if (chart.config.type !== 'doughnut') return;
        if (chart.config.options?.plugins?.pie3d?.enabled) return; // พาย 3D ไม่มีรูตรงกลาง

        const meta = chart.getDatasetMeta(0);
        const arc = meta && meta.data && meta.data[0];
        if (!arc || !arc.innerRadius) return;

        const { x: centerX, y: centerY, innerRadius } = arc;
        const ctx = chart.ctx;
        const data = chart.config.data.datasets[0].data;
        const total = data.reduce((a, b) => a + Number(b || 0), 0);
        const totalLabel = `${total.toLocaleString('th-TH')} งาน`;
        const maxTextWidth = innerRadius * 2 * 0.8; // เว้นขอบในรูไว้ ~20%

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // บรรทัดล่าง: ตัวเลขรวม - ลดขนาดจนวัดแล้วพอดีความกว้างรู (ไม่มีวันล้น)
        let numFontSize = Math.max(11, innerRadius * 0.34);
        ctx.font = `600 ${numFontSize}px Kanit, sans-serif`;
        while (numFontSize > 9 && ctx.measureText(totalLabel).width > maxTextWidth) {
            numFontSize -= 1;
            ctx.font = `600 ${numFontSize}px Kanit, sans-serif`;
        }

        // บรรทัดบน: ป้ายกำกับเล็กกว่าตัวเลขเสมอ (สัดส่วนคงที่ กันซ้อนทับ)
        const capFontSize = Math.max(8, numFontSize * 0.42);
        ctx.font = `${capFontSize}px Kanit, sans-serif`;
        ctx.fillStyle = CHART_INK.muted;
        ctx.fillText('ทั้งหมด', centerX, centerY - numFontSize * 0.62);

        ctx.font = `600 ${numFontSize}px Kanit, sans-serif`;
        ctx.fillStyle = '#0b0b0b';
        ctx.fillText(totalLabel, centerX, centerY + numFontSize * 0.52);

        ctx.restore();
    }
});

// ✅ Plugin กราฟวงกลม 3 มิติแบบแยกชิ้น (exploded 3D pie) ตามภาพตัวอย่างที่ user เลือก
// - ไม่ให้ Chart.js วาดวง 2D เดิม (beforeDatasetDraw คืน false) แล้ววาดพาย 3D เองทั้งหมด
// - tooltip/legend ของ Chart.js ยังใช้งานได้ (hit-test จากวง 2D ที่ซ่อนไว้ + hover ดันชิ้นออก)
// หมายเหตุ UX: มุมเอียง 3D บิดขนาดชิ้นที่ตามองเห็น จึงให้ tooltip แสดงจำนวน/เปอร์เซ็นต์จริงเสมอ
function shadeHexColor(hex, factor) {
    const c = String(hex).replace('#', '');
    const num = parseInt(c.length === 3 ? c.split('').map(ch => ch + ch).join('') : c, 16);
    const ch = v => Math.max(0, Math.min(255, Math.round(v * factor)));
    return `rgb(${ch((num >> 16) & 255)}, ${ch((num >> 8) & 255)}, ${ch(num & 255)})`;
}
// มุมเริ่มของพาย 3D: ให้กึ่งกลางชิ้นที่ใหญ่ที่สุดชี้ขึ้นบน-ซ้าย (-135°) ชิ้นเล็กที่เหลือ
// จะไปรวมกันด้านล่าง-ขวา เอียงลง ~45° ตามที่ user ต้องการ
function pie3dStartAngle(chart) {
    const values = chart.config.data.datasets[0].data.map(v => Number(v) || 0);
    const shown = values.map((v, i) => (chart.getDataVisibility(i) ? v : 0));
    const total = shown.reduce((a, b) => a + b, 0);
    if (!total) return -Math.PI / 2;
    const big = shown.indexOf(Math.max(...shown));
    const before = shown.slice(0, big).reduce((a, b) => a + b, 0);
    const midOffset = ((before + shown[big] / 2) / total) * Math.PI * 2;
    return -3 * Math.PI / 4 - midOffset;
}
Chart.register({
    id: 'pie3d',
    beforeUpdate: function (chart) {
        // หมุนวง 2D ที่ซ่อนไว้ให้ตรงกับพาย 3D ด้วย tooltip ตอน hover จะได้ตรงชิ้น
        if (!chart.config.options?.plugins?.pie3d?.enabled) return;
        chart.config.options.rotation = (pie3dStartAngle(chart) + Math.PI / 2) * 180 / Math.PI;
    },
    // ✅ ลูกเล่น "นูนขึ้น" ตอนโหลดกราฟเสร็จ: เริ่มจากแบนราบแล้วค่อยๆ พองความหนา+ขนาดขึ้นมา
    // เหมือนวงกลมดันตัวเองขึ้นจากพื้นจนเต็มรูป 3D (แทนที่จะโผล่มาเต็มทันที)
    afterUpdate: function (chart) {
        if (!chart.config.options?.plugins?.pie3d?.enabled) return;
        const dur = 1000; // เผื่อเวลาให้ "นูนทีละสี" ครบทุกชิ้น (stagger + เวลานูนของชิ้นสุดท้าย)
        chart.$pie3dGrow = { t0: performance.now(), dur };
        const step = (now) => {
            if (!chart.$pie3dGrow) return;
            const t = Math.min(1, (now - chart.$pie3dGrow.t0) / dur);
            if (chart.canvas) chart.draw();
            if (t < 1) {
                requestAnimationFrame(step);
            } else {
                chart.$pie3dGrow = null;
                if (chart.canvas) chart.draw();
            }
        };
        requestAnimationFrame(step);
    },
    beforeDatasetDraw: function (chart) {
        if (chart.config.options?.plugins?.pie3d?.enabled) return false; // ซ่อนวง 2D เดิม
    },
    afterDatasetsDraw: function (chart) {
        const opts = chart.config.options?.plugins?.pie3d;
        if (!opts || !opts.enabled) return;

        const dataset = chart.config.data.datasets[0];
        const values = dataset.data.map(v => Number(v) || 0);
        const colors = dataset.backgroundColor;

        const tilt = opts.tilt || 0.5;       // อัตราส่วนแกนตั้ง/แกนนอนของวงรี (มุมเอียง)

        // แอนิเมชัน "นูนขึ้นทีละสี": แต่ละชิ้นเริ่มนูนช้ากว่ากันตามลำดับ (stagger) แล้วค่อยๆ
        // พองขนาด+ความหนาของ "ชิ้นนั้นเอง" ขึ้นมา ไล่กันไปทีละสีรอบวง
        const elapsed = chart.$pie3dGrow ? (performance.now() - chart.$pie3dGrow.t0) : Infinity;
        const staggerMs = 90, popDur = 420;
        function growAt(order) {
            const t = Math.min(1, Math.max(0, (elapsed - order * staggerMs) / popDur));
            return 1 - Math.pow(1 - t, 3); // easeOutCubic
        }
        const fullDepth = opts.depth || 28;  // ความหนาเต็มที่ (ใช้คำนวณ layout คงที่ ไม่ให้กระตุก)
        const depth = fullDepth;             // ความหนาที่ใช้จัดวาง/เงา (ของจริงต่อชิ้นจะคูณ growT เอง)
        const explode = opts.explode || 10;  // ระยะแยกชิ้นออกจากศูนย์กลาง (px)
        const hoverLift = 10;                // ชิ้นที่ hover ดันออกเพิ่ม

        // คิดเฉพาะชิ้นที่ไม่ได้ถูกซ่อนจาก legend
        const shownTotal = values.reduce((a, v, i) => a + (chart.getDataVisibility(i) ? v : 0), 0);
        if (!shownTotal) return;

        // วางวงรีให้พอดีพื้นที่กราฟ (หักที่ให้ความหนา + ระยะแยกชิ้น)
        const area = chart.chartArea;
        const pad = explode + hoverLift + 4;
        // labelSpace / labelMinWidth ปรับได้ต่อกราฟ (ชื่อเรื่อง IT ยาวกว่าชื่อสถานะ) ค่าเริ่มต้นเท่าเดิม
        const showLabels = opts.labels !== false && (area.right - area.left) >= (opts.labelMinWidth || 560);
        const labelSpace = showLabels ? (opts.labelSpace || 190) : 0; // พื้นที่ข้างละ สำหรับป้าย "ชื่อ + จำนวน + %"
        const rx = Math.max(10, Math.min(
            (area.right - area.left) / 2 - pad - labelSpace,
            ((area.bottom - area.top) - depth - pad * 2) / (2 * tilt)
        ));
        const ry = rx * tilt; // ขนาดเต็ม (layout คงที่) - การ "นูน" ทีละชิ้นคูณสัดส่วนนี้เอาตอนวาดแทน
        const cx = (area.left + area.right) / 2;
        const cy = (area.top + area.bottom) / 2 - depth / 2;

        const active = new Set(chart.getActiveElements().map(a => a.index));

        // มุมของแต่ละชิ้น (วนตามเข็ม เริ่มจากมุมที่ทำให้ชิ้นเล็กมาอยู่ด้านหน้า)
        let angle = pie3dStartAngle(chart);
        const slices = [];
        values.forEach((v, i) => {
            if (!v || !chart.getDataVisibility(i)) return;
            const sweep = (v / shownTotal) * Math.PI * 2;
            const mid = angle + sweep / 2;
            const off = explode + (active.has(i) ? hoverLift : 0);
            slices.push({
                i, order: slices.length, a0: angle, a1: angle + sweep, mid,
                x: cx + Math.cos(mid) * off,
                y: cy + Math.sin(mid) * off * tilt
            });
            angle += sweep;
        });

        const ctx = chart.ctx;
        const pt = (s, a, srx, sry) => [s.x + Math.cos(a) * srx, s.y + Math.sin(a) * sry];

        function sideFace(s, a, color, srx, sry, sdepth) { // หน้าตัดแนวรัศมี (ด้านข้างของชิ้น)
            const [px, py] = pt(s, a, srx, sry);
            ctx.beginPath();
            ctx.moveTo(s.x, s.y);
            ctx.lineTo(px, py);
            ctx.lineTo(px, py + sdepth);
            ctx.lineTo(s.x, s.y + sdepth);
            ctx.closePath();
            ctx.fillStyle = color;
            ctx.fill();
        }
        function rimWall(s, color, srx, sry, sdepth) { // ผนังโค้งด้านนอก (เห็นเฉพาะช่วงครึ่งล่าง 0..π)
            const twoPi = Math.PI * 2;
            for (let k = -1; k <= 1; k++) {
                const s0 = Math.max(s.a0, k * twoPi), s1 = Math.min(s.a1, k * twoPi + Math.PI);
                if (s1 <= s0) continue;
                const [ex, ey] = pt(s, s1, srx, sry);
                ctx.beginPath();
                ctx.ellipse(s.x, s.y, srx, sry, 0, s0, s1, false);
                ctx.lineTo(ex, ey + sdepth);
                ctx.ellipse(s.x, s.y + sdepth, srx, sry, 0, s1, s0, true);
                ctx.closePath();
                const g = ctx.createLinearGradient(s.x - srx, 0, s.x + srx, 0);
                g.addColorStop(0, shadeHexColor(color, 0.62));
                g.addColorStop(0.5, shadeHexColor(color, 0.85));
                g.addColorStop(1, shadeHexColor(color, 0.62));
                ctx.fillStyle = g;
                ctx.fill();
            }
        }

        ctx.save();

        // เงาฟุ้งใต้พาย ให้ดูลอยจากพื้นแบบในภาพตัวอย่าง
        ctx.save();
        ctx.beginPath();
        ctx.ellipse(cx, cy + depth + 6, rx + explode, ry + explode * tilt, 0, 0, Math.PI * 2);
        ctx.shadowColor = 'rgba(90, 40, 130, 0.30)';
        ctx.shadowBlur = 26;
        ctx.fillStyle = 'rgba(90, 40, 130, 0.10)';
        ctx.fill();
        ctx.restore();

        // วาดเป็น 3 รอบ: หน้าตัดข้าง -> ผนังโค้ง -> หน้าบน (หน้าบนทับด้านข้างที่ถูกบังเสมอ
        // กันหน้าตัดของชิ้นเล็กโผล่ทับชิ้นใหญ่ที่อยู่ใกล้กว่า) แต่ละรอบเรียงชิ้นหลังไปหน้า
        const ordered = slices.slice().sort((p, q) => Math.sin(p.mid) - Math.sin(q.mid));
        ordered.forEach(s => {
            const gp = growAt(s.order);
            if (gp <= 0) return;
            const srx = rx * (0.5 + 0.5 * gp), sry = ry * (0.5 + 0.5 * gp), sdepth = fullDepth * gp;
            const sideColor = shadeHexColor(colors[s.i], 0.55);
            [s.a0, s.a1].sort((p, q) => Math.sin(p) - Math.sin(q)).forEach(a => sideFace(s, a, sideColor, srx, sry, sdepth));
        });
        ordered.forEach(s => {
            const gp = growAt(s.order);
            if (gp <= 0) return;
            const srx = rx * (0.5 + 0.5 * gp), sry = ry * (0.5 + 0.5 * gp), sdepth = fullDepth * gp;
            rimWall(s, colors[s.i], srx, sry, sdepth);
        });
        ordered.forEach(s => {
            const gp = growAt(s.order);
            if (gp <= 0) return;
            const srx = rx * (0.5 + 0.5 * gp), sry = ry * (0.5 + 0.5 * gp);
            const base = colors[s.i];
            // หน้าบน (top face) ไล่เฉดให้ดูมันวาวเล็กน้อย
            ctx.beginPath();
            ctx.moveTo(s.x, s.y);
            ctx.ellipse(s.x, s.y, srx, sry, 0, s.a0, s.a1, false);
            ctx.closePath();
            const g = ctx.createLinearGradient(s.x, s.y - ry, s.x, s.y + ry);
            g.addColorStop(0, shadeHexColor(base, 1.15));
            g.addColorStop(1, base);
            ctx.fillStyle = g;
            ctx.fill();
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.55)';
            ctx.stroke();
        });

        // ป้ายชื่อข้างชิ้น: อ่านค่าได้ทันทีไม่ต้อง hover (ชิ้นเล็กในพาย 3D แทบมองไม่เห็น)
        // แบ่งซ้าย/ขวาตามทิศของชิ้น แล้วดันป้ายให้ห่างกันอย่างน้อย 22px กันซ้อนทับ
        if (showLabels) {
            const labels = chart.config.data.labels;
            const gap = 22;
            const items = slices.map(s => {
                const front = Math.sin(s.mid) > 0;
                return {
                    s,
                    right: Math.cos(s.mid) >= 0,
                    ax: s.x + Math.cos(s.mid) * rx * 0.85,
                    ay: s.y + Math.sin(s.mid) * ry * 0.85,
                    y: cy + Math.sin(s.mid) * (ry + 26) + (front ? depth : 0)
                };
            });
            [true, false].forEach(side => {
                const list = items.filter(it => it.right === side).sort((p, q) => p.y - q.y);
                const top = area.top + 8, bottom = area.bottom - 8;
                for (let k = 0; k < list.length; k++) {
                    list[k].y = Math.max(list[k].y, k ? list[k - 1].y + gap : top);
                }
                for (let k = list.length - 1; k >= 0; k--) {
                    list[k].y = Math.min(list[k].y, k < list.length - 1 ? list[k + 1].y - gap : bottom);
                }
            });

            ctx.font = '13px Kanit, sans-serif';
            ctx.textBaseline = 'middle';
            items.forEach(it => {
                const dir = it.right ? 1 : -1;
                const elbowX = cx + dir * (rx + explode + 18);
                const textX = elbowX + dir * 8;
                const v = values[it.s.i];
                const pct = ((v / shownTotal) * 100).toFixed(1);

                ctx.beginPath();
                ctx.moveTo(it.ax, it.ay);
                ctx.lineTo(elbowX, it.y);
                ctx.lineTo(textX - dir * 3, it.y);
                ctx.strokeStyle = CHART_INK.muted;
                ctx.lineWidth = 1;
                ctx.stroke();
                ctx.beginPath();
                ctx.arc(it.ax, it.ay, 2.5, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.fill();
                ctx.stroke();

                // "ชื่อสถานะ" สีหมึกปกติ + "จำนวน (%)" ตัวหนา ให้ตัวเลขเด่นกว่า
                const name = labels[it.s.i] + '  ';
                const num = `${v.toLocaleString('th-TH')} งาน (${pct}%)`;
                ctx.font = '13px Kanit, sans-serif';
                const nameW = ctx.measureText(name).width;
                ctx.font = '600 13px Kanit, sans-serif';
                const numW = ctx.measureText(num).width;
                const x0 = it.right ? textX : textX - nameW - numW;
                ctx.textAlign = 'left';
                ctx.font = '13px Kanit, sans-serif';
                ctx.fillStyle = CHART_INK.text;
                ctx.fillText(name, x0, it.y);
                ctx.font = '600 13px Kanit, sans-serif';
                ctx.fillStyle = '#0b0b0b';
                ctx.fillText(num, x0 + nameW, it.y);
            });
        }

        ctx.restore();
    }
});

// ตั้งค่ากลางของโดนัท/กราฟแท่งให้ดูโปร่ง สบายตา (surface gap ระหว่างชิ้น/แท่ง)
const DOUGHNUT_BASE_OPTIONS = {
    cutout: '68%',
    radius: '92%'
};
const DOUGHNUT_DATASET_EXTRA = {
    borderColor: '#ffffff',
    borderWidth: 2,
    hoverBorderWidth: 2,
    spacing: 2
};
function barGridOptions() {
    return {
        x: {
            grid: { display: false },
            ticks: { color: CHART_INK.muted }
        },
        y: {
            beginAtZero: true,
            grid: { color: CHART_INK.grid },
            ticks: { color: CHART_INK.muted }
        }
    };
}

// ✅ ไอคอนวงแหวนหมุน "กำลังโหลด" คลุมกราฟไว้ระหว่างรอข้อมูลจาก server (ฉีด CSS ครั้งเดียว)
(function injectSpinnerStyle() {
    if (document.getElementById('pie3d-spinner-style')) return;
    const style = document.createElement('style');
    style.id = 'pie3d-spinner-style';
    style.textContent = `
        .pie3d-spinner-overlay {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            background: rgba(255, 255, 255, 0.6); z-index: 5; border-radius: inherit;
        }
        .pie3d-spinner-ring { width: 52px; height: 52px; animation: pie3dSpinRotate 1.4s linear infinite; }
        .pie3d-spinner-ring circle {
            fill: none; stroke: ${CHART_HUE.blue}; stroke-width: 5; stroke-linecap: round;
            stroke-dasharray: 150; stroke-dashoffset: 0;
            transform-origin: center; animation: pie3dSpinDraw 1.4s ease-in-out infinite;
        }
        /* ✅ วงกลมค่อยๆ ลากเส้นก่อตัวขึ้นรอบวง แล้วหดกลับ วนซ้ำ พร้อมตัววงหมุนไปเรื่อยๆ */
        @keyframes pie3dSpinRotate { to { transform: rotate(360deg); } }
        @keyframes pie3dSpinDraw {
            0%   { stroke-dashoffset: 148; transform: rotate(0deg); }
            50%  { stroke-dashoffset: 38;  transform: rotate(135deg); }
            100% { stroke-dashoffset: 148; transform: rotate(450deg); }
        }
    `;
    document.head.appendChild(style);
})();
// ✅ อัตราส่วนกราฟพาย 3D: จอกว้าง (desktop) ใช้วงรีเตี้ยกว้าง ส่วนจอแคบ (มือถือ) ต้อง
// เผื่อความสูงมากกว่านี้ ไม่งั้นวงรีจะถูกบีบจนแบนเหลือเป็นก้อนเล็กๆ (bug ที่เจอบนมือถือ)
function pie3dAspectRatio() {
    return window.innerWidth < 576 ? 1.05 : 1.7;
}
function toggleChartSpinner(canvasId, show) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const wrap = canvas.closest('.chart-wrapper') || canvas.parentElement;
    if (!wrap) return;
    if (getComputedStyle(wrap).position === 'static') wrap.style.position = 'relative';
    let overlay = wrap.querySelector('.pie3d-spinner-overlay');
    if (show) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'pie3d-spinner-overlay';
            overlay.innerHTML = '<svg class="pie3d-spinner-ring" viewBox="0 0 52 52"><circle cx="26" cy="26" r="21"></circle></svg>';
            wrap.appendChild(overlay);
        }
    } else if (overlay) {
        overlay.remove();
    }
}

// ✅ ฟังก์ชันดึงข้อมูลจาก data_one.php แล้ววาดกราฟโดนัท
// แก้ เพิ่ม month, year, department = '%' , fetch(`./data/data_one.php?month=${month}&year=${year}&department=${department}`)
function fetchChartData(month, year, department = '%') {
    toggleChartSpinner('statusChart_one', true);
    fetch(`./data/data_one.php?month=${month}&year=${year}&department=${department}`)
        .then(response => response.json())
        .then(result => {
            // ✅ รวมจำนวนแต่ละสถานะทั้ง 12 เดือน (ใช้ reduce)
            const sums = {
                'งานเข้าใหม่': result.incomingJobs.reduce((a, b) => a + b, 0),
                'รอดำเนินการ': result.waitingProcess.reduce((a, b) => a + b, 0),
                'กำลังดำเนินการ': result.inProgress.reduce((a, b) => a + b, 0),
                'รออนุมัติรับงาน': result.waitingAcceptApproval.reduce((a, b) => a + b, 0),
                'รออนุมัติส่งงาน': result.waitingSubmitApproval.reduce((a, b) => a + b, 0),
                'ปิดงาน': result.closed.reduce((a, b) => a + b, 0),
                'สำเร็จ (รอปิดงาน)': result.completedPendingClose.reduce((a, b) => a + b, 0),
                // ✅ รวมทุกสถานะ "ไม่อนุมัติ" ให้เหลือหมวดเดียว กันกราฟรกด้วยชิ้นสีซ้ำ
                'ไม่อนุมัติ': result.rejectedAccept.reduce((a, b) => a + b, 0)
                    + result.rejectedGeneral.reduce((a, b) => a + b, 0)
            };

            const chartData = {
                labels: JOB_STATUS_ORDER,
                datasets: [{
                    data: JOB_STATUS_ORDER.map(k => sums[k]),
                    backgroundColor: JOB_STATUS_ORDER.map(k => JOB_STATUS_COLOR[k]),
                    ...DOUGHNUT_DATASET_EXTRA
                }]
            };

            const config = {
                type: 'doughnut',
                data: chartData,
                options: {
                    ...DOUGHNUT_BASE_OPTIONS,
                    responsive: true,
                    aspectRatio: pie3dAspectRatio(), // พาย 3D เป็นวงรี - อัตราส่วนปรับตามความกว้างจอ
                    plugins: {
                        title: {
                            display: true,
                            text: 'สถานะงานรายเดือน',
                            color: '#0b0b0b',
                            font: { size: 15, weight: '600' },
                            padding: { bottom: 12 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                color: CHART_INK.text,
                                font: { family: 'Kanit, sans-serif' },
                                filter: (item, data) => (data.datasets[0].data[item.index] || 0) > 0
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} งาน (${percent}%)`;
                                }
                            }
                        },
                        // ✅ กราฟวงกลม 3D เอียง+แยกชิ้น (ดูฟังก์ชัน pie3d plugin ด้านบน)
                        pie3d: { enabled: true, tilt: 0.62, depth: 22, explode: 10 }
                    },
                    animation: {
                        duration: 700,
                        easing: 'easeOutQuart'
                    }
                },
                plugins: ['centerText']
            };

            // ✅ ถ้ามีกราฟอยู่แล้ว ต้อง destroy ก่อนสร้างใหม่
            if (window.statusChartInstance) {
                window.statusChartInstance.destroy();
            }

            // ✅ สร้างกราฟใหม่
            window.statusChartInstance = new Chart(
                document.getElementById('statusChart_one'),
                config
            );
            toggleChartSpinner('statusChart_one', false);
        })
        .catch(err => {
            console.error('โหลดข้อมูลผิดพลาด:', err);
            toggleChartSpinner('statusChart_one', false);
        });
}


// ✅ Event เปลี่ยนเดือน/ปี -> โหลดข้อมูลใหม่
// ['monthSelect', 'yearSelect'].forEach(id => {
//     document.getElementById(id).addEventListener('change', () => {
//         const month = document.getElementById('monthSelect').value;
//         const year = document.getElementById('yearSelect').value;
//         fetchChartData(month, year);
//         fetchChartData2(year);
//         fetchChartData2_3(month,year);
//     });
// });
// ✅ ใช้ไฟล์เดียว ครอบจักรวาลหลายหน้า
(function () {
    const $m = document.getElementById('monthSelect');
    const $y = document.getElementById('yearSelect');
    const $dept = document.getElementById('departmentSelect');

    const pad = n => String(n).padStart(2, '0');
    const now = new Date();
    const defM = pad(now.getMonth() + 1);
    const defY = String(now.getFullYear());

    if ($m && !$m.value) $m.value = defM;
    if ($y && !$y.value) $y.value = defY;

    function hasFn(name) { return typeof window[name] === 'function'; }
    function hasEl(id) { return !!document.getElementById(id); }

    function getDept() {
        // หน้า IT ไม่มี select → fix 18, หน้าที่มี select → ใช้ค่าที่เลือก
        return $dept?.value || '18';
    }

    function updateForThisPage() {
        const month = $m?.value || defM;
        const year = $y?.value || defY;
        const dept = getDept();

        // 🟦 หน้า “กราฟโดนัทรายเดือนทั่วไป” (มี canvas id=statusChart_one)
        if (hasEl('statusChart_one') && hasFn('fetchChartData')) {
            try { Promise.resolve(fetchChartData(month, year, dept)).catch(console.error); } catch (e) { console.error(e); }
        }

        // 🟧 หน้า “กราฟแท่งรายปีทั่วไป” (มี canvas id=statusChart)
        if (hasEl('statusChart') && hasFn('fetchChartData2')) {
            try { Promise.resolve(fetchChartData2(year, dept)).catch(console.error); } catch (e) { console.error(e); }
        }

        // 🟥 หน้า “Dashboard IT” (มี canvas id=statusChartIT)
        if (hasEl('statusChartIT') && hasFn('fetchChartData2_3')) {
            // แนะนำให้ใช้ signature = (month, year, department)
            try { Promise.resolve(fetchChartData2_3(month, year, '18')).catch(console.error); } catch (e) { console.error(e); }
        }
    }

    // โหลดครั้งแรก
    updateForThisPage();

    // เปลี่ยนเดือน/ปี/ฝ่าย → อัปเดตเฉพาะของ “หน้านี้”
    [$m, $y, $dept].forEach(el => el?.addEventListener('change', updateForThisPage));

    // กัน error async หลุด
    window.addEventListener('unhandledrejection', e => console.error('[unhandledrejection]', e.reason));
})();




function fetchChartData2(year, department = '%') {
    fetch(`./data/data_js.php?year=${year}&department=${department}`)
        .then(response => response.json())
        .then(result => {
            const seriesByStatus = {
                'งานเข้าใหม่': result.incomingJobs,
                'รอดำเนินการ': result.waitingProcess,
                'กำลังดำเนินการ': result.inProgress,
                'รออนุมัติรับงาน': result.waitingAcceptApproval,
                'รออนุมัติส่งงาน': result.waitingSubmitApproval,
                'ปิดงาน': result.closed,
                'สำเร็จ (รอปิดงาน)': result.completedPendingClose,
                // ✅ รวมทุกสถานะ "ไม่อนุมัติ" เป็นเส้นเดียว กันกราฟรกด้วยแท่งสีซ้ำ
                'ไม่อนุมัติ': sumArrays(result.rejectedAccept, result.rejectedGeneral)
            };

            const yearlyData = {
                labels: result.labels,
                datasets: JOB_STATUS_ORDER.map(k => ({
                    label: k,
                    data: seriesByStatus[k],
                    backgroundColor: JOB_STATUS_COLOR[k],
                    maxBarThickness: 22
                }))
            };

            const yearlyConfig = {
                type: 'bar',
                data: yearlyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // ให้กราฟยืดเต็มความสูง/กว้างของ .chart-wrapper--tall แทนสัดส่วนเริ่มต้นของ bar chart
                    scales: {
                        ...barGridOptions(),
                        y: {
                            ...barGridOptions().y,
                            title: {
                                display: true,
                                text: 'จำนวนงานรวมต่อเดือน',
                                color: CHART_INK.muted
                            }
                        },
                        x: {
                            ...barGridOptions().x,
                            title: {
                                display: true,
                                text: 'เดือน',
                                color: CHART_INK.muted
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'สถานะงานรายปี',
                            color: '#0b0b0b',
                            font: { size: 15, weight: '600' },
                            padding: { bottom: 12 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                color: CHART_INK.text
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    animation: {
                        duration: 700,
                        easing: 'easeOutQuart'
                    }
                }
            };

            // ✅ destroy กราฟเก่าก่อนสร้างใหม่
            if (window.yearlyChartInstance) {
                window.yearlyChartInstance.destroy();
            }

            window.yearlyChartInstance = new Chart(
                document.getElementById('statusChart'),
                yearlyConfig
            );
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการโหลดข้อมูล:', error);
        });
}


// function fetchChartData2_3(month, year, department = '18') {
//     const qs = new URLSearchParams({ month, year, department });
//     fetch(`./data/data_jsIT.php?${qs.toString()}`)
//         .then(r => r.json())
//         .then(result => {
//             // ให้สีอัตโนมัติ (คงที่ตามลำดับ)
//             const colored = result.datasets.map((ds, i) => ({
//                 ...ds,
//                 backgroundColor: `hsl(${(i * 47) % 360} 70% 55% / 0.7)`
//             }));

//             const cfg = {
//                 type: 'bar',
//                 data: {
//                     labels: result.labels,      // 12 เดือน หรือ 1 เดือน
//                     datasets: colored           // ชุดข้อมูล = แต่ละ problem
//                 },
//                 options: {
//                     responsive: true,
//                     scales: {
//                         y: { beginAtZero: true, title: { display: true, text: 'จำนวนงาน' } },
//                         x: { title: { display: true, text: 'เดือน' } }
//                     },
//                     plugins: {
//                         title: { display: true, text: 'กราฟตามเรื่อง (Problem) - IT' },
//                         legend: { position: 'bottom' },
//                         tooltip: { mode: 'index', intersect: false }
//                     }
//                 }
//             };

//             if (window.yearlyChartInstance) window.yearlyChartInstance.destroy();
//             window.yearlyChartInstance = new Chart(document.getElementById('statusChartIT'), cfg);
//         })
//         .catch(err => console.error('โหลดข้อมูลผิดพลาด:', err));
// }


/* =====================================================================
   ✅ กราฟ Dashboard IT (canvas #statusChartIT)

   เดิมเป็น grouped bar: 1 เรื่อง = 1 ชุดข้อมูล × 12 เดือน → หัวข้อ IT ~9 เรื่อง
   กลายเป็น ~108 แท่งผอมเรียงกัน เทียบอะไรไม่ได้เลย (เกิน 7±2 ของ Miller's Law)
   และตอนเลือก "เดือนนี้" ซึ่งเป็นค่าเริ่มต้นของหน้า เหลือ label เดียว → แท่งผอม
   9 แท่งลอยกลางพื้นที่ 460px

   ของใหม่เลือกรูปแบบกราฟตามช่วงเวลาที่ผู้ใช้กรองไว้:
     - ช่วง = หลายเดือน → แท่ง 3 มิติ (isometric) ซ้อนรายเรื่อง 1 เดือน = 1 กล่อง
       สูง = งานรวมเดือนนั้น, แต่ละชั้นสี = 1 เรื่องที่ Request (part-to-whole over time)
     - ช่วง = เดือนเดียว หรือผู้ใช้กดดู "อันดับเรื่อง" → แท่งนอนเรียงอันดับ
       (แนวนอนเพราะชื่อเรื่องภาษาไทยยาว อ่านเต็มๆ ได้โดยไม่ต้องเอียงหัว)
   ===================================================================== */

// สีรายเรื่อง: 8 hue ชุดหลักของระบบ (ลำดับคงที่ห้ามสลับ) + น้ำตาล/เขียวหัวเป็ด อีก 2
// ฝ่าย IT มีเรื่องในระบบ 11 เรื่อง ในนั้นเป็น "อื่นๆ" อยู่แล้ว 1 → เรื่องจริง 10 เรื่อง
// จึงต้องมี 10 สี + สี "อื่นๆ" อีก 1 ให้ครบทุกเรื่องพอดี
// (เกินช่วง 7±2 ของ Miller's Law อยู่บ้าง แต่ผู้ใช้ต้องการเห็นครบทุกเรื่องที่ฝ่ายมี
//  จึงให้ความครบถ้วนมาก่อน แล้วชดเชยด้วยการเรียงมาก->น้อย + spotlight ตอนชี้ legend)
// ไม่ใช้ CHART_HUE.red (#e34948) เพราะใกล้ส้ม #eb6834 เกินไป (ระยะ RGB 38 / สว่างต่าง 1.24 เท่า)
// สลับเป็นแดงไวน์ #b3123f แทน ห่างจากส้มเป็น 103 และห่างจากชมพู 155
const IT_HUE_ORDER = [CHART_HUE.blue, CHART_HUE.orange, CHART_HUE.aqua, CHART_HUE.yellow,
    CHART_HUE.magenta, CHART_HUE.green, CHART_HUE.violet, '#b3123f', '#8a5a2b', '#0e7490'];
const IT_MAX_SUBJECTS = IT_HUE_ORDER.length;

// สีของกอง "อื่นๆ": ยังเป็นโทนกลางอยู่ เพราะถ้าให้สีจัดจ้านเท่า 7 เรื่องหลัก มันจะดู
// เป็นหมวดที่มีความหมายพอๆ กัน ทั้งที่จริงเป็นแค่ถังรวมของที่เหลือ
// แต่เปลี่ยนจากเทาขุ่น #9a9890 เป็น slate น้ำเงินอมเทา: 4.8:1 บนพื้นขาว (เดิม 2.8:1)
// และต่างจากม่วง #4a3aa7 ที่อยู่ติดกันในกอง 1.8 เท่า
const IT_OTHER_COLOR = '#64748b';

// สร้างตาราง "ชื่อเรื่อง -> สี" โดยไล่ตามลำดับที่ server ส่งมา (problem ORDER BY id)
// ห้ามไล่ตามยอดมาก->น้อย เพราะพอยอดเปลี่ยนเดือน อันดับก็สลับ สีของเรื่องเดิมจะเปลี่ยนตามไปด้วย
// ยึดลำดับ id แทน เรื่องหนึ่งจึงเป็นสีเดิมตลอด ทุกตัวกรอง ทุกช่วงเวลา และทั้งสองมุมมอง
function itSubjectColors(datasetsInServerOrder) {
    const map = new Map();
    let hue = 0;
    datasetsInServerOrder.forEach(ds => {
        if (ds.label === 'อื่นๆ' || hue >= IT_MAX_SUBJECTS) { map.set(ds.label, IT_OTHER_COLOR); return; }
        map.set(ds.label, IT_HUE_ORDER[hue++]);
    });
    return map;
}

// เฉดของแท่ง 3 มิติ: สมมติแสงตกจากด้านบน หน้าบนจึงสว่างสุด → หน้าแท่ง → ด้านข้างเข้มสุด
// คำนวณจากสีประจำเรื่องตัวเดียว สีจึงยังอ่านออกว่าเป็นเรื่องไหนแม้จะมี 3 หน้า
const IT_BAR3D_SHADE = { top: 1.25, frontTop: 1.05, frontBottom: .88, side: .7, hover: 1.14 };
const IT_BAR3D_LIFT = 6; // ระยะที่กล่องลอยขึ้นตอนเมาส์ชี้

let itChartPayload = null;  // ข้อมูลชุดล่าสุดจาก server → สลับมุมมองได้ทันทีโดยไม่ต้องยิงใหม่
let itChartMode = 'auto';   // 'auto' (= วงกลม) | 'pie' | 'subject'

function lhReducedMotion() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
}

// เก็บว่าตอนนี้เมาส์ชี้ชื่อเรื่องไหนใน legend แล้ววาดใหม่ทันที
// legend.onHover ยิงถี่มาก จึงวาดเฉพาะตอนค่าเปลี่ยนจริง
function setITSpotlight(chart, datasetIndex) {
    if (!chart || chart.$itSpotlight === datasetIndex) return;
    chart.$itSpotlight = datasetIndex;
    // แท่ง 2 มิติ: ชี้ชื่อเรื่องใน legend → เรื่องอื่นจางลง เห็นเรื่องนั้นทุกเดือนชัดๆ (Von Restorff)
    if (!chart.config.options?.plugins?.lhBar3D?.enabled) {
        chart.data.datasets.forEach((ds, i) => {
            if (ds.$base === undefined) ds.$base = ds.backgroundColor;
            const faded = typeof ds.$base === 'string' && /^#[0-9a-f]{6}$/i.test(ds.$base) ? ds.$base + '33' : ds.$base;
            ds.backgroundColor = (datasetIndex === null || datasetIndex === undefined || i === datasetIndex) ? ds.$base : faded;
        });
        chart.update('none');
        return;
    }
    chart.draw();
}

// ✅ แถบไฮไลต์หลังแท่งที่เมาส์ชี้อยู่ (ลูกเล่น): ช่วยล็อกสายตาไว้ที่เดือน/เรื่องเดียว
// ตอนกวาดเมาส์อ่านกราฟที่มีหลายแท่ง
Chart.register({
    id: 'lhHoverBand',
    beforeDatasetsDraw(chart) {
        if (!chart.config.options?.plugins?.lhHoverBand?.enabled) return;
        const active = chart.getActiveElements();
        if (!active.length) return;
        const el = chart.getDatasetMeta(active[0].datasetIndex).data[active[0].index];
        if (!el) return;

        const area = chart.chartArea;
        const horizontal = chart.config.options?.indexAxis === 'y';
        const ctx = chart.ctx;
        ctx.save();
        ctx.fillStyle = 'rgba(42, 120, 214, .07)';
        const band = horizontal ? (el.height || 18) + 10 : (el.width || 18) + 14;
        if (horizontal) {
            ctx.fillRect(area.left, el.y - band / 2, area.right - area.left, band);
        } else {
            ctx.fillRect(el.x - band / 2, area.top, band, area.bottom - area.top);
        }
        ctx.restore();
    }
});

// ✅ แท่ง 3 มิติแบบ isometric (ดึงความลึกขึ้นไปทางขวาบน)
// วาดเอง 3 หน้า: หน้าบน (สว่างสุด) → ด้านข้าง → หน้าแท่ง เพื่อให้ขอบหน้าแท่งคมอยู่บนสุด
// ใช้วิธีเดียวกับ plugin pie3d ของหน้าอื่นในระบบ: ปิดการวาดแท่ง 2D เดิมแล้ววาดทับเอง
// เรขาคณิตอ่านจาก element ของ Chart.js ทุกเฟรม แอนิเมชันแท่งงอกขึ้นจึงยังทำงานตามปกติ
Chart.register({
    id: 'lhBar3D',
    beforeDatasetDraw(chart) {
        if (chart.config.options?.plugins?.lhBar3D?.enabled) return false;
    },
    afterDatasetsDraw(chart) {
        const opt = chart.config.options?.plugins?.lhBar3D;
        if (!opt?.enabled) return;

        const ctx = chart.ctx;
        const S = IT_BAR3D_SHADE;
        const active = new Set(chart.getActiveElements().map(a => a.index));
        const quad = (p) => {
            ctx.beginPath();
            ctx.moveTo(p[0][0], p[0][1]);
            for (let i = 1; i < p.length; i++) ctx.lineTo(p[i][0], p[i][1]);
            ctx.closePath();
            ctx.fill();
        };

        // ชี้ที่ชื่อเรื่องใน legend → ไล่เรื่องนั้นให้เด่นขึ้นมาทุกเดือน เรื่องอื่นจางลง
        // ช่วยไล่สายตาตามเรื่องเดียวข้ามทั้ง 12 กอง โดยไม่ต้องปิดเรื่องอื่นทิ้งทีละอัน
        const spot = chart.$itSpotlight;

        ctx.save();
        for (let i = 0; i < chart.data.labels.length; i++) {
            // รวบชิ้นของเดือนนี้จากทุกเรื่องที่ยังไม่ถูกปิดจาก legend และมีค่ามากกว่า 0
            const segs = [];
            chart.data.datasets.forEach((ds, di) => {
                if (!chart.isDatasetVisible(di)) return;
                const bar = chart.getDatasetMeta(di).data[i];
                if (!bar || !(bar.base - bar.y > 0.5)) return;
                segs.push({ bar, di, color: String(ds.backgroundColor || IT_OTHER_COLOR) });
            });
            if (!segs.length) continue; // เดือนที่ไม่มีงาน ไม่ต้องวาดกล่องแบนๆ ทิ้งไว้

            const on = active.has(i);
            const lit = on ? S.hover : 1;
            const rise = on ? IT_BAR3D_LIFT : 0; // กล่องลอยขึ้นตอนเมาส์ชี้ (ลูกเล่นเดียวกับพายที่ดันชิ้นออก)
            const ref = segs[0].bar;
            const w = Math.max(4, ref.width || 0);
            const d = Math.min(20, Math.max(6, w * 0.34)); // ความลึกยืดตามความกว้างแท่ง กัน 12 เดือนแล้วชนกัน
            const left = ref.x - w / 2, right = ref.x + w / 2;

            let top = segs[0];
            let foot = segs[0].bar.base;
            segs.forEach(s => {
                if (s.bar.y < top.bar.y) top = s; // ชิ้นบนสุดของกอง = ชิ้นเดียวที่เห็นหน้าบน
                if (s.bar.base > foot) foot = s.bar.base;
            });
            const topY = top.bar.y - rise;

            // เงาใต้กล่อง: หล่อจากเงาของทั้งกองครั้งเดียว แล้ววาดหน้าจริงทับ
            // ทำให้กล่องดูวางอยู่บนพื้น ไม่ลอยแปะอยู่เฉยๆ และตอนยกขึ้นเงาจะฟุ้งตาม
            ctx.save();
            ctx.shadowColor = 'rgba(15,23,42,.22)';
            ctx.shadowBlur = on ? 18 : 10;
            ctx.shadowOffsetY = on ? 10 : 4;
            // เติมขาวไม่ใช่สีเข้ม: เงาหล่อจากรูปทรงไม่ได้หล่อจากสี ถ้าเติมเข้มแล้วเรื่องที่ถูก
            // ทำให้จางตอน spotlight จะมีพื้นดำทะลุขึ้นมาแทนที่จะซีดลง
            ctx.fillStyle = '#ffffff';
            quad([[left, foot - rise], [left, topY], [left + d, topY - d], [right + d, topY - d],
                [right + d, foot - rise - d], [right, foot - rise]]);
            ctx.restore();

            segs.forEach(s => {
                const y = s.bar.y - rise, b = s.bar.base - rise;
                ctx.globalAlpha = (spot == null || spot === s.di) ? 1 : .18;
                // ด้านข้างขวา: เฉดเข้มสุดของสีเรื่องนั้น
                ctx.fillStyle = shadeHexColor(s.color, S.side * lit);
                quad([[right, y], [right + d, y - d], [right + d, b - d], [right, b]]);
                // หน้าแท่ง: ไล่เฉดอ่อน->เข้มในสีเดียวกัน ให้เห็นความโค้งของกล่อง
                const g = ctx.createLinearGradient(0, y, 0, b);
                g.addColorStop(0, shadeHexColor(s.color, S.frontTop * lit));
                g.addColorStop(1, shadeHexColor(s.color, S.frontBottom * lit));
                ctx.fillStyle = g;
                ctx.fillRect(left, y, w, b - y);

                // เส้นคั่นรอยต่อระหว่างชั้น: คู่สีที่ความสว่างใกล้กัน (เช่น เหลือง/ชมพู)
                // วางติดกันแล้วขอบจะกลืน เส้นขาวบางๆ ช่วยแยกชั้นโดยไม่ต้องเปลี่ยนชุดสี
                ctx.strokeStyle = 'rgba(255,255,255,.65)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(left, y);
                ctx.lineTo(right, y);
                ctx.lineTo(right + d, y - d);
                ctx.stroke();
            });

            ctx.globalAlpha = (spot == null || spot === top.di) ? 1 : .18;
            ctx.fillStyle = shadeHexColor(top.color, S.top * lit);
            quad([[left, topY], [right, topY], [right + d, topY - d], [left + d, topY - d]]);
            ctx.globalAlpha = 1;
        }
        ctx.restore();
    }
});

// ✅ ตัวเลขกำกับบนแท่ง: อ่านค่าได้จากตัวแท่งเลย ไม่ต้องกวาดสายตาไปแกนแล้วย้อนกลับมา (Fitts's Law)
//    โหมดอันดับ = ค่าที่ปลายแท่ง / โหมดรายเดือน = ยอดรวมเหนือแท่ง
Chart.register({
    id: 'lhBarValue',
    afterDatasetsDraw(chart) {
        const opt = chart.config.options?.plugins?.lhBarValue;
        if (!opt?.enabled) return;
        const ctx = chart.ctx;
        const fmt = v => Number(v).toLocaleString('th-TH');
        ctx.save();
        ctx.font = '600 11px Kanit, sans-serif';
        ctx.fillStyle = CHART_INK.text;

        if (opt.mode === 'rank') {
            const meta = chart.getDatasetMeta(0);
            const values = chart.data.datasets[0].data;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            meta.data.forEach((bar, i) => {
                const v = Number(values[i]) || 0;
                if (v > 0) ctx.fillText(fmt(v), bar.x + 8, bar.y);
            });
        } else if (opt.mode === 'grouped') {
            // แท่งแยกข้างกัน: ตัวเลขบนหัวแต่ละแท่ง (ข้ามแท่ง 0 และแท่งที่แคบจนตัวเลขจะชนกัน)
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            chart.data.datasets.forEach((ds, di) => {
                if (!chart.isDatasetVisible(di)) return;
                chart.getDatasetMeta(di).data.forEach((bar, i) => {
                    const v = Number(ds.data[i]) || 0;
                    if (v > 0 && (bar.width || 0) >= 10) ctx.fillText(fmt(v), bar.x, bar.y - 4);
                });
            });
        } else {
            const n = chart.data.labels.length;
            const area = chart.chartArea;
            if (n > 0 && (area.right - area.left) / n >= 26) { // แคบกว่านี้ตัวเลขจะชนกัน
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                const lifted = new Set(chart.getActiveElements().map(a => a.index));
                for (let i = 0; i < n; i++) {
                    let total = 0, topY = null, ref = null;
                    chart.data.datasets.forEach((ds, di) => {
                        if (!chart.isDatasetVisible(di)) return;
                        const bar = chart.getDatasetMeta(di).data[i];
                        if (!bar) return;
                        ref = ref || bar;
                        const v = Number(ds.data[i]) || 0;
                        total += v;
                        if (v > 0 && (topY === null || bar.y < topY)) topY = bar.y;
                    });
                    if (!(total > 0) || topY === null || !ref) continue;
                    // เลื่อนตามความลึกของแท่ง 3 มิติ ให้ตัวเลขลอยเหนือ "หน้าบน" ไม่ทับกล่อง
                    // และลอยขึ้นพร้อมกล่องตอนเมาส์ชี้ ไม่งั้นตัวเลขจะค้างจมอยู่ในกล่อง
                    const d = Math.min(20, Math.max(6, (ref.width || 0) * 0.34));
                    const rise = lifted.has(i) ? IT_BAR3D_LIFT : 0;
                    ctx.fillText(fmt(total), ref.x + d / 2, topY - rise - d - 6);
                }
            }
        }
        ctx.restore();
    }
});

// อัปเดตสรุปบนหัวกราฟ (ยอดรวม + เรื่องที่มาบ่อยที่สุด)
// ทุกจุดเช็ค element ก่อนเสมอ เพราะ Dashboardit.php / Dashboardit2.php ใช้ฟังก์ชันนี้ร่วมกันแต่ไม่มีหัวกราฟใหม่
function updateITChartHead(grandTotal, topLabel, topValue) {
    const set = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
    set('itChartTotal', Number(grandTotal).toLocaleString('th-TH'));
    set('itChartTopName', topLabel || '—');
    set('itChartTopValue', topValue ? `${Number(topValue).toLocaleString('th-TH')} งาน` : '—');
}

function toggleITChartEmpty(show) {
    const empty = document.getElementById('itChartEmpty');
    const canvas = document.getElementById('statusChartIT');
    if (empty) empty.hidden = !show;
    if (canvas) canvas.style.visibility = show ? 'hidden' : '';
}

function renderITChart() {
    const canvas = document.getElementById('statusChartIT');
    if (!canvas || !itChartPayload) return;

    const labels = itChartPayload.labels || [];
    const sumOf = ds => ds.data.reduce((a, b) => a + (Number(b) || 0), 0);

    // เก็บทุกเรื่องที่ฝ่ายมีในระบบ รวมเรื่องที่ยังไม่มีงานในช่วงนี้ (แสดงเป็น 0)
    // เพราะการที่เรื่องหนึ่ง "ไม่มีงานเลย" ก็เป็นข้อมูลที่ต้องรู้ ไม่ใช่สิ่งที่ควรซ่อน
    const live = (itChartPayload.datasets || [])
        .map(ds => ({ label: String(ds.label ?? ''), data: (ds.data || []).map(v => Number(v) || 0) }));
    const grand = live.reduce((a, ds) => a + sumOf(ds), 0);

    if (window.itChartInstance) { window.itChartInstance.destroy(); window.itChartInstance = null; }

    if (!grand) {
        updateITChartHead(0, '—', 0);
        toggleITChartEmpty(true);
        return;
    }
    toggleITChartEmpty(false);

    const ranked = live.slice().sort((a, b) => sumOf(b) - sumOf(a));
    updateITChartHead(grand, ranked[0].label, sumOf(ranked[0]));

    // เรื่องเดียวกันต้องเป็นสีเดียวกันทั้งสองมุมมอง สลับมุมมองแล้วสายตาจึงตามเรื่องเดิมได้
    const subjectColors = itSubjectColors(live); // live = ลำดับเดิมจาก server (ห้ามใช้ ranked)
    const colorOf = label => subjectColors.get(label) || IT_OTHER_COLOR;

    // ค่าเริ่มต้น = วงกลม 3D แบบเดียวกับหน้า Dashboard หลัก (Jakob's Law / Repetition)
    // วงกลมใช้ได้ทุกช่วงเวลา (แม้เดือนเดียว) จึงไม่ต้องบังคับสลับมุมมองเหมือนกราฟรายเดือนเดิม
    const view = itChartMode === 'subject' ? 'subject' : 'pie';
    document.querySelectorAll('[data-it-chart-mode]').forEach(btn => {
        btn.disabled = false;
        btn.classList.toggle('is-active', btn.dataset.itChartMode === view);
        btn.setAttribute('aria-selected', btn.dataset.itChartMode === view ? 'true' : 'false');
    });

    const animation = lhReducedMotion() ? false : {
        duration: 600,
        easing: 'easeOutQuart',
        // ไล่ขึ้นทีละแท่ง ให้ภาษาการเคลื่อนไหวเป็นชุดเดียวกับกราฟวงกลม 3D ของหน้าอื่น (Repetition)
        delay: ctx => (ctx.type === 'data' && ctx.mode === 'default') ? ctx.dataIndex * 35 + ctx.datasetIndex * 45 : 0
    };

    let cfg;
    if (view === 'subject') {
        const rows = ranked; // ทุกเรื่องที่ฝ่ายมี เรียงมาก->น้อย เรื่องที่ยังไม่มีงานจะไปกองท้ายเป็น 0
        const short = s => (s.length > 26 ? s.slice(0, 25) + '…' : s);
        cfg = {
            type: 'bar',
            data: {
                labels: rows.map(r => short(r.label)),
                datasets: [{
                    label: 'จำนวนงาน',
                    data: rows.map(sumOf),
                    backgroundColor: rows.map(r => colorOf(r.label)),
                    hoverBackgroundColor: rows.map(r => shadeHexColor(colorOf(r.label), .78)),
                    maxBarThickness: 26
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { right: 34 } }, // เผื่อที่ให้ตัวเลขท้ายแท่ง
                scales: {
                    x: { beginAtZero: true, grid: { color: CHART_INK.grid }, ticks: { color: CHART_INK.muted, precision: 0 } },
                    y: { grid: { display: false }, ticks: { color: CHART_INK.text, font: { size: 12 } } }
                },
                plugins: {
                    legend: { display: false }, // ชื่อเรื่องอยู่บนแกนแล้ว legend จะซ้ำซ้อน
                    lhBarValue: { enabled: true, mode: 'rank' },
                    lhHoverBand: { enabled: true },
                    tooltip: {
                        callbacks: {
                            title: items => rows[items[0].dataIndex].label, // ชื่อเต็ม (แกนตัดให้สั้น)
                            label: item => {
                                const v = item.parsed.x;
                                return ` ${v.toLocaleString('th-TH')} งาน (${((v / grand) * 100).toFixed(1)}% ของทั้งหมด)`;
                            }
                        }
                    }
                },
                animation
            }
        };
    } else {
        // วงกลม 3D (ปลั๊กอิน pie3d ตัวเดียวกับหน้า Dashboard หลัก): 1 ชิ้น = 1 เรื่อง ขนาด = สัดส่วนงาน
        // แสดงครบทั้ง 11 หัวข้อของฝ่าย IT ไม่พับรวม และเรียงตามลำดับเดียวกับเมนู "เรื่องที่แจ้ง"
        // (ลำดับจาก server = ตาราง problem ORDER BY id) ผู้ใช้หาเรื่องในกราฟเจอในตำแหน่งที่คุ้น (Jakob's Law)
        // เรื่องที่ยังไม่มีงานไม่มีชิ้นในวง แต่ยังอยู่ใน legend พร้อมเลข 0 สีจาง ให้รู้ว่ามีหัวข้อนี้ (ไม่ซ่อนข้อมูล)
        const slices = live.map(ds => ({ label: ds.label, value: sumOf(ds), color: colorOf(ds.label) }));
        // ชื่อบนป้ายข้างชิ้น: ยาวสุด 26 ตัวอักษร (พอสำหรับ "แก้ไขปัญหาระบบเครือข่าย Internet" เกือบเต็ม)
        const short = s => (s.length > 26 ? s.slice(0, 25) + '…' : s);

        cfg = {
            type: 'doughnut',
            data: {
                labels: slices.map(s => short(s.label)),
                datasets: [{
                    data: slices.map(s => s.value),
                    backgroundColor: slices.map(s => s.color),
                    ...DOUGHNUT_DATASET_EXTRA
                }]
            },
            options: {
                ...DOUGHNUT_BASE_OPTIONS,
                responsive: true,
                maintainAspectRatio: false, // กล่อง .chart-wrapper สูงตายตัวอยู่แล้ว
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8,
                            color: CHART_INK.text, font: { family: 'Kanit, sans-serif' },
                            // legend = ชื่อเต็ม + จำนวน ครบ 11 หัวข้อ, หัวข้อที่เป็น 0 ใช้สีจาง
                            generateLabels: chart => Chart.overrides.doughnut.plugins.legend.labels.generateLabels(chart)
                                .map((item, i) => {
                                    const s = slices[i];
                                    item.text = `${s.label} (${s.value.toLocaleString('th-TH')})`;
                                    if (!s.value) item.fontColor = '#9ca3af';
                                    return item;
                                })
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: items => slices[items[0].dataIndex].label, // ชื่อเต็ม
                            label: item => {
                                const v = item.parsed;
                                return ` ${v.toLocaleString('th-TH')} งาน (${((v / grand) * 100).toFixed(1)}%)`;
                            }
                        }
                    },
                    // ชื่อเรื่อง IT ยาว → กันที่ป้ายข้างละ 300px และโชว์ป้ายเมื่อกราฟกว้าง ≥ 980px
                    // จอแคบกว่านั้นซ่อนป้าย ใช้ legend ด้านล่าง (ซึ่งมีชื่อ + จำนวนครบ) แทน
                    pie3d: { enabled: true, tilt: 0.62, depth: 22, explode: 10, labelSpace: 300, labelMinWidth: 980 }
                },
                animation: lhReducedMotion() ? false : { duration: 700, easing: 'easeOutQuart' }
            }
        };
    }

    window.itChartInstance = new Chart(canvas, cfg);
}

// ปุ่มสลับมุมมอง: ใช้ข้อมูลชุดเดิมที่ cache ไว้ จึงเปลี่ยนภาพทันที ไม่มีรอโหลด (Doherty Threshold)
document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('[data-it-chart-mode]');
    if (!btn || btn.disabled) return;
    itChartMode = btn.dataset.itChartMode;
    renderITChart();
});

function fetchChartData2_3(month, year, department = '18', filters = {}) {
    const qs = new URLSearchParams({
        month,
        year,
        department,
        // ฟิลเตอร์เสริม (ปล่อยว่างได้)
        search_subject: filters.subject || '',
        search_status: filters.status || '',
        search_department: filters.department || '',
        date_from: filters.date_from || '',
        date_to: filters.date_to || ''
    });

    toggleChartSpinner('statusChartIT', true); // เดิมไม่มีสถานะระหว่างรอ กราฟค้างภาพเก่าไว้เฉยๆ
    return fetch(`./data/data_jsIT.php?${qs.toString()}`)
        .then(r => r.json())
        .then(result => {
            if (!result || !result.ok) throw new Error('bad response');
            itChartPayload = result;
            renderITChart();
        })
        .catch(err => console.error('โหลดข้อมูลผิดพลาด:', err))
        .finally(() => toggleChartSpinner('statusChartIT', false));
}





///////////////////////////////////////////////

// ✅ งานส่งออก - กราฟโดนัท
function fetchChartData1(month, year, department = '%') {
    toggleChartSpinner('statusChart_one1', true);
    fetch(`./data/data_one1.php?month=${month}&year=${year}&department=${department}`)
        .then(response => response.json())
        .then(result => {
            const sums = {
                'งานเข้าใหม่': result.incomingJobs.reduce((a, b) => a + b, 0),
                'รอดำเนินการ': result.waitingProcess.reduce((a, b) => a + b, 0),
                'กำลังดำเนินการ': result.inProgress.reduce((a, b) => a + b, 0),
                'รออนุมัติรับงาน': result.waitingAcceptApproval.reduce((a, b) => a + b, 0),
                'รออนุมัติส่งงาน': result.waitingSubmitApproval.reduce((a, b) => a + b, 0),
                'ปิดงาน': result.closed.reduce((a, b) => a + b, 0),
                'สำเร็จ (รอปิดงาน)': result.completedPendingClose.reduce((a, b) => a + b, 0),
                // ✅ รวมทุกสถานะ "ไม่อนุมัติ" ให้เหลือหมวดเดียว
                'ไม่อนุมัติ': result.rejectedAccept.reduce((a, b) => a + b, 0)
                    + result.rejectedSubmit.reduce((a, b) => a + b, 0)
                    + result.rejectedGeneral.reduce((a, b) => a + b, 0)
            };

            const chartData = {
                labels: JOB_STATUS_ORDER,
                datasets: [{
                    data: JOB_STATUS_ORDER.map(k => sums[k]),
                    backgroundColor: JOB_STATUS_ORDER.map(k => JOB_STATUS_COLOR[k]),
                    ...DOUGHNUT_DATASET_EXTRA
                }]
            };

            const config = {
                type: 'doughnut',
                data: chartData,
                options: {
                    ...DOUGHNUT_BASE_OPTIONS,
                    responsive: true,
                    aspectRatio: pie3dAspectRatio(), // พาย 3D เป็นวงรี - อัตราส่วนปรับตามความกว้างจอ
                    plugins: {
                        title: {
                            display: true,
                            text: 'สถานะงานรายเดือน',
                            color: '#0b0b0b',
                            font: { size: 15, weight: '600' },
                            padding: { bottom: 12 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                color: CHART_INK.text,
                                font: { family: 'Kanit, sans-serif' },
                                filter: (item, data) => (data.datasets[0].data[item.index] || 0) > 0
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} งาน (${percent}%)`;
                                }
                            }
                        },
                        // ✅ กราฟวงกลม 3D เอียง+แยกชิ้น (ดูฟังก์ชัน pie3d plugin ด้านบน)
                        pie3d: { enabled: true, tilt: 0.62, depth: 22, explode: 10 }
                    },
                    animation: {
                        duration: 700,
                        easing: 'easeOutQuart'
                    }
                },
                plugins: ['centerText']
            };

            if (window.statusChartInstance1) {
                window.statusChartInstance1.destroy();
            }
            window.statusChartInstance1 = new Chart(
                document.getElementById('statusChart_one1'),
                config
            );
            toggleChartSpinner('statusChart_one1', false);
        })
        .catch(err => {
            console.error('โหลดข้อมูลผิดพลาด:', err);
            toggleChartSpinner('statusChart_one1', false);
        });
}

// ✅ งานส่งออก - กราฟแท่งรายปี
function fetchChartData2_1(year, department = '%') {
    fetch(`./data/data_js1.php?year=${year}&department=${department}`)
        .then(response => response.json())
        .then(result => {
            const seriesByStatus = {
                'งานเข้าใหม่': result.incomingJobs,
                'รอดำเนินการ': result.waitingProcess,
                'กำลังดำเนินการ': result.inProgress,
                'รออนุมัติรับงาน': result.waitingAcceptApproval,
                'รออนุมัติส่งงาน': result.waitingSubmitApproval,
                'ปิดงาน': result.closed,
                'สำเร็จ (รอปิดงาน)': result.completedPendingClose,
                // ✅ รวมทุกสถานะ "ไม่อนุมัติ" เป็นเส้นเดียว
                'ไม่อนุมัติ': sumArrays(result.rejectedAccept, result.rejectedSubmit, result.rejectedGeneral)
            };

            const yearlyData = {
                labels: result.labels,
                datasets: JOB_STATUS_ORDER.map(k => ({
                    label: k,
                    data: seriesByStatus[k],
                    backgroundColor: JOB_STATUS_COLOR[k],
                    maxBarThickness: 22
                }))
            };

            const yearlyConfig = {
                type: 'bar',
                data: yearlyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // ให้กราฟยืดเต็มความสูง/กว้างของ .chart-wrapper--tall แทนสัดส่วนเริ่มต้นของ bar chart
                    scales: {
                        ...barGridOptions(),
                        y: {
                            ...barGridOptions().y,
                            title: {
                                display: true,
                                text: 'จำนวนงานรวมต่อเดือน',
                                color: CHART_INK.muted
                            }
                        },
                        x: {
                            ...barGridOptions().x,
                            title: {
                                display: true,
                                text: 'เดือน',
                                color: CHART_INK.muted
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'สถานะงานรายปี',
                            color: '#0b0b0b',
                            font: { size: 15, weight: '600' },
                            padding: { bottom: 12 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                color: CHART_INK.text
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    animation: {
                        duration: 700,
                        easing: 'easeOutQuart'
                    }
                }
            };

            if (window.yearlyChartInstance1) {
                window.yearlyChartInstance1.destroy();
            }
            window.yearlyChartInstance1 = new Chart(
                document.getElementById('statusChart1'),
                yearlyConfig
            );
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการโหลดข้อมูล:', error);
        });
}



/////////////////////////////////////  งานเกินกำหนด

function fetchChartData4(month, year, department = '%') {
    toggleChartSpinner('statusChart_one2', true);
    fetch(`./data/data_one2.php?month=${month}&year=${year}&department=${department}`)
        .then(response => response.json())
        .then(result => {
            // นับยอดรวมทั้งเดือน (หรือจะนับทั้งปีเปลี่ยน array.reduce แบบเดิม)
            const totalLate = result.lateJobs.reduce((a, b) => a + b, 0);
            const totalOnTime = result.onTimeJobs.reduce((a, b) => a + b, 0);

            const chartData = {
                labels: [
                    'งานไม่เกินกำหนด',
                    'งานเกินกำหนด'
                ],
                datasets: [{
                    data: [
                        totalOnTime,
                        totalLate
                    ],
                    backgroundColor: [STATUS_GOOD, STATUS_CRITICAL],
                    ...DOUGHNUT_DATASET_EXTRA
                }]
            };

            const config = {
                type: 'doughnut',
                data: chartData,
                options: {
                    ...DOUGHNUT_BASE_OPTIONS,
                    responsive: true,
                    aspectRatio: pie3dAspectRatio(), // พาย 3D เป็นวงรี - อัตราส่วนปรับตามความกว้างจอ
                    plugins: {
                        title: {
                            display: true,
                            text: 'งานเกินกำหนด / ไม่เกินกำหนด',
                            color: '#0b0b0b',
                            font: { size: 15, weight: '600' },
                            padding: { bottom: 12 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                color: CHART_INK.text,
                                font: { family: 'Kanit, sans-serif' },
                                filter: (item, data) => (data.datasets[0].data[item.index] || 0) > 0
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} งาน (${percent}%)`;
                                }
                            }
                        },
                        // ✅ กราฟวงกลม 3D เอียง+แยกชิ้น (ดูฟังก์ชัน pie3d plugin ด้านบน)
                        pie3d: { enabled: true, tilt: 0.62, depth: 22, explode: 10 }
                    },
                    animation: {
                        duration: 700,
                        easing: 'easeOutQuart'
                    }
                },
                plugins: ['centerText']
            };

            if (window.lateJobChartInstance) {
                window.lateJobChartInstance.destroy();
            }
            window.lateJobChartInstance = new Chart(
                document.getElementById('statusChart_one2'),
                config
            );
            toggleChartSpinner('statusChart_one2', false);
        })
        .catch(err => {
            console.error('โหลดข้อมูลผิดพลาด:', err);
            toggleChartSpinner('statusChart_one2', false);
        });
}


// ✅ งานส่งออก - กราฟแท่งรายปี
function fetchChartData2_2(year, department = '%') {
    fetch(`./data/data_js2.php?year=${year}&department=${department}`)
        .then(response => response.json())
        .then(result => {
            // สมมติ backend ส่ง lateJobs กับ onTimeJobs (array 12 เดือน)
            const yearlyData = {
                labels: result.labels,
                datasets: [
                    {
                        label: 'งานไม่เกินกำหนด',
                        data: result.onTimeJobs,
                        backgroundColor: STATUS_GOOD,
                        maxBarThickness: 22
                    },
                    {
                        label: 'งานเกินกำหนด',
                        data: result.lateJobs,
                        backgroundColor: STATUS_CRITICAL,
                        maxBarThickness: 22
                    }
                ]
            };

            const yearlyConfig = {
                type: 'bar',
                data: yearlyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // ให้กราฟยืดเต็มความสูง/กว้างของ .chart-wrapper--tall แทนสัดส่วนเริ่มต้นของ bar chart
                    scales: {
                        ...barGridOptions(),
                        y: {
                            ...barGridOptions().y,
                            title: {
                                display: true,
                                text: 'จำนวนงานรวมต่อเดือน',
                                color: CHART_INK.muted
                            }
                        },
                        x: {
                            ...barGridOptions().x,
                            title: {
                                display: true,
                                text: 'เดือน',
                                color: CHART_INK.muted
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'งานเกินกำหนด/ไม่เกินกำหนด รายปี',
                            color: '#0b0b0b',
                            font: { size: 15, weight: '600' },
                            padding: { bottom: 12 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                color: CHART_INK.text
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    animation: {
                        duration: 700,
                        easing: 'easeOutQuart'
                    }
                }
            };

            if (window.yearlyChartInstance1) {
                window.yearlyChartInstance1.destroy();
            }
            window.yearlyChartInstance1 = new Chart(
                document.getElementById('statusChart2'),
                yearlyConfig
            );
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการโหลดข้อมูล:', error);
        });
}

