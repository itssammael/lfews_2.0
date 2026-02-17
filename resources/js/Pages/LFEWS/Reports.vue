<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, watch, nextTick, onUnmounted, computed } from 'vue';
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';
import { Exporting } from "@amcharts/amcharts5/plugins/exporting";
import axios from 'axios';
import * as XLSX from 'xlsx';



interface Sensor {
    id: number;
    name: string;
}

interface Station {
    id: number;
    name: string;
}

const props = defineProps<{
    sensors: Sensor[];
    stations: Station[];
    waterLevelYears: number[];
    weatherStationYears: number[];
}>();

const months = [
    'January', 'February', 'March', 'April', 'May', 'June', 
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Form states
const selectedReport = ref('Rain');
const reportTypes = ['Rain', 'Heat Index', 'Water Level'];
const rainReportType = ref('Monthly');
const heatIndexReportType = ref('Monthly');
const waterLevelReportType = ref('Monthly');

const currentMonth = new Date().toLocaleString('default', { month: 'long' });
const currentYear = new Date().getFullYear();

const detailRainReport = ref({
    month: currentMonth,
    year: currentYear,
    from: '',
    to: '',
    station: 'All'
});

const heatIndexReport = ref({
    month: currentMonth, // Default Month
    year: currentYear,
    from: '',
    to: '',
    station: 'All'
});

const waterLevelReport = ref({
    sensor: 'All',
    year: currentYear,
    month: currentMonth,
    from: '',
    to: ''
});

const isGenerating = ref(false);
const hasSearched = ref(false);

// Chart Refs
const rainChartDiv = ref<HTMLElement | null>(null);
const heatIndexChartDiv = ref<HTMLElement | null>(null);
const waterLevelChartDiv = ref<HTMLElement | null>(null);

const waterLevelRecords = ref<any[]>([]);
const waterLevelSummaryRecords = ref<any[]>([]);
const waterLevelThresholds = ref<any>(null);
const rainRecords = ref<any[]>([]);
const rainSummaryRecords = ref<any[]>([]);
const heatIndexRecords = ref<any[]>([]);
const heatIndexSummaryRecords = ref<any[]>([]);

// Tab States
const waterLevelActiveTab = ref('');
const rainActiveTab = ref('');
const heatIndexActiveTab = ref('');

// Tab Lists
const waterLevelTabs = computed(() => {
    const sensors = [...new Set(waterLevelRecords.value.map(r => r.sensor_name))];
    const tabs = sensors.sort();
    if (waterLevelReport.value.sensor === 'All' && waterLevelSummaryRecords.value.length > 0) {
        return ['Summary', ...tabs];
    }
    return tabs;
});

const rainTabs = computed(() => {
    const stations = [...new Set(rainRecords.value.map(r => r.station_name))];
    const tabs = stations.sort();
    if (detailRainReport.value.station === 'All' && rainSummaryRecords.value.length > 0) {
        return ['Summary', ...tabs];
    }
    return tabs;
});

const heatIndexTabs = computed(() => {
    const stations = [...new Set(heatIndexRecords.value.map(r => r.station_name))];
    const tabs = stations.sort();
    if (heatIndexReport.value.station === 'All' && heatIndexSummaryRecords.value.length > 0) {
        return ['Summary', ...tabs];
    }
    return tabs;
});

// Filtered Records (based on tab)
const filteredWaterLevelRecords = computed(() => {
    if (waterLevelReport.value.sensor !== 'All') return waterLevelRecords.value;
    const activeTab = waterLevelActiveTab.value || (waterLevelTabs.value.length > 0 ? waterLevelTabs.value[0] : '');
    if (activeTab === 'Summary') return waterLevelSummaryRecords.value;
    return waterLevelRecords.value.filter(r => r.sensor_name === activeTab);
});

const filteredRainRecords = computed(() => {
    if (detailRainReport.value.station !== 'All') return rainRecords.value;
    const activeTab = rainActiveTab.value || (rainTabs.value.length > 0 ? rainTabs.value[0] : '');
    if (activeTab === 'Summary') return rainSummaryRecords.value;
    return rainRecords.value.filter(r => r.station_name === activeTab);
});

const filteredHeatIndexRecords = computed(() => {
    if (heatIndexReport.value.station !== 'All') return heatIndexRecords.value;
    const activeTab = heatIndexActiveTab.value || (heatIndexTabs.value.length > 0 ? heatIndexTabs.value[0] : '');
    if (activeTab === 'Summary') return heatIndexSummaryRecords.value;
    return heatIndexRecords.value.filter(r => r.station_name === activeTab);
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = 17;

const paginatedWaterLevelRecords = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredWaterLevelRecords.value.slice(start, start + itemsPerPage);
});
const waterLevelTotalPages = computed(() => Math.ceil(filteredWaterLevelRecords.value.length / itemsPerPage));

const paginatedRainRecords = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredRainRecords.value.slice(start, start + itemsPerPage);
});
const rainTotalPages = computed(() => Math.ceil(filteredRainRecords.value.length / itemsPerPage));

const paginatedHeatIndexRecords = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredHeatIndexRecords.value.slice(start, start + itemsPerPage);
});
const heatIndexTotalPages = computed(() => Math.ceil(filteredHeatIndexRecords.value.length / itemsPerPage));

// Matrix Summary Logic
const getDaysInMonth = (monthNameOrIndex: string | number, year: number) => {
    let monthIndex;
    if (typeof monthNameOrIndex === 'string') {
        monthIndex = months.indexOf(monthNameOrIndex);
    } else {
        monthIndex = (monthNameOrIndex as number) - 1;
    }
    if (monthIndex === -1) return 31;
    return new Date(year, monthIndex + 1, 0).getDate();
};

const rainSummaryMatrix = computed(() => {
    if (detailRainReport.value.station !== 'All' || rainSummaryRecords.value.length === 0) return null;
    const stations = [...new Set(rainRecords.value.map(r => r.station_name))].sort();
    let days: any[] = [];
    if (rainReportType.value === 'Monthly') {
        const year = parseInt(detailRainReport.value.year);
        const numDays = getDaysInMonth(detailRainReport.value.month, year);
        for (let i = 1; i <= numDays; i++) days.push({ label: i.toString(), day: i });
    } else {
        const start = new Date(detailRainReport.value.from);
        const end = new Date(detailRainReport.value.to);
        let curr = new Date(start);
        while (curr <= end) {
            days.push({ label: curr.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), dateStr: curr.toISOString().split('T')[0] });
            curr.setDate(curr.getDate() + 1);
        }
    }
    const rows = days.map(day => {
        const row: any = { dateLabel: day.label };
        stations.forEach(station => {
            const record = rainSummaryRecords.value.find(r => {
                const rDate = new Date(r.date_time);
                return r.station_name === station && (rainReportType.value === 'Monthly' ? rDate.getDate() === day.day : r.date_time.startsWith(day.dateStr));
            });
            row[station] = record ? record.precipitation_total : '-';
        });
        return row;
    });
    const totals = stations.reduce((acc: any, s) => {
        const vals = rows.map(r => r[s]).filter(v => typeof v === 'number');
        acc[s] = vals.reduce((sum, v) => sum + v, 0).toFixed(2);
        return acc;
    }, {});
    const averages = stations.reduce((acc: any, s) => {
        const vals = rows.map(r => r[s]).filter(v => typeof v === 'number');
        acc[s] = vals.length > 0 ? (vals.reduce((sum, v) => sum + v, 0) / vals.length).toFixed(2) : '0.00';
        return acc;
    }, {});
    return { stations, rows, totals, averages };
});

const heatIndexSummaryMatrix = computed(() => {
    if (heatIndexReport.value.station !== 'All' || heatIndexSummaryRecords.value.length === 0) return null;
    const stations = [...new Set(heatIndexRecords.value.map(r => r.station_name))].sort();
    let days: any[] = [];
    if (heatIndexReportType.value === 'Monthly') {
        const year = parseInt(heatIndexReport.value.year);
        const numDays = getDaysInMonth(heatIndexReport.value.month, year);
        for (let i = 1; i <= numDays; i++) days.push({ label: i.toString(), day: i });
    } else {
        const start = new Date(heatIndexReport.value.from);
        const end = new Date(heatIndexReport.value.to);
        let curr = new Date(start);
        while (curr <= end) {
            days.push({ label: curr.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), dateStr: curr.toISOString().split('T')[0] });
            curr.setDate(curr.getDate() + 1);
        }
    }
    const rows = days.map(day => {
        const row: any = { dateLabel: day.label };
        stations.forEach(station => {
            const record = heatIndexSummaryRecords.value.find(r => {
                const rDate = new Date(r.date_time);
                return r.station_name === station && (heatIndexReportType.value === 'Monthly' ? rDate.getDate() === day.day : r.date_time.startsWith(day.dateStr));
            });
            row[station] = record ? record.heat_index : '-';
        });
        return row;
    });
    const averages = stations.reduce((acc: any, s) => {
        const vals = rows.map(r => r[s]).filter(v => typeof v === 'number');
        acc[s] = vals.length > 0 ? (vals.reduce((sum, v) => sum + v, 0) / vals.length).toFixed(2) : '0.00';
        return acc;
    }, {});
    return { stations, rows, averages };
});

const waterLevelSummaryMatrix = computed(() => {
    if (waterLevelReport.value.sensor !== 'All' || waterLevelSummaryRecords.value.length === 0) return null;
    const sensors = [...new Set(waterLevelRecords.value.map(r => r.sensor_name))].sort();
    let days: any[] = [];
    if (waterLevelReportType.value === 'Monthly') {
        const year = parseInt(waterLevelReport.value.year);
        const numDays = getDaysInMonth(waterLevelReport.value.month, year);
        for (let i = 1; i <= numDays; i++) days.push({ label: i.toString(), day: i });
    } else {
        const start = new Date(waterLevelReport.value.from);
        const end = new Date(waterLevelReport.value.to);
        let curr = new Date(start);
        while (curr <= end) {
            days.push({ label: curr.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), dateStr: curr.toISOString().split('T')[0] });
            curr.setDate(curr.getDate() + 1);
        }
    }
    const rows = days.map(day => {
        const row: any = { dateLabel: day.label };
        sensors.forEach(sensor => {
            const record = waterLevelSummaryRecords.value.find(r => {
                const rDate = new Date(r.date_time);
                return r.sensor_name === sensor && (waterLevelReportType.value === 'Monthly' ? rDate.getDate() === day.day : r.date_time.startsWith(day.dateStr));
            });
            row[sensor] = record ? record.water_level : '-';
        });
        return row;
    });
    const averages = sensors.reduce((acc: any, s) => {
        const vals = rows.map(r => r[s]).filter(v => typeof v === 'number');
        acc[s] = vals.length > 0 ? (vals.reduce((sum, v) => sum + v, 0) / vals.length).toFixed(2) : '0.00';
        return acc;
    }, {});
    return { sensors, rows, averages };
});

let activeChartRoot: am5.Root | null = null;

const colors = [
    0xfd7b38, // Complementary Orange (for Blue)
    0xef467e, // Complementary Pink/Red (for Green)
    0x0a61f4, // Complementary Blue (for Amber)
    0x10bbb5, // Complementary Cyan/Teal (for Red)
    0x74a309, // Complementary Olive (for Violet)
    0x13b766, // Complementary Green (for Pink)
];

const setupChartRoot = (element: HTMLElement) => {
    const root = am5.Root.new(element);
    root.setThemes([am5themes_Animated.new(root)]);
    return root;
};

const initChart = (rootElement: HTMLElement, title: string, data: any[], seriesNames: string[] = [], isBar: boolean = false, unit: string = '') => {
    const root = setupChartRoot(rootElement);
    
    const chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: true,
        panY: true,
        wheelX: "panX",
        wheelY: "zoomX",
        pinchZoomX: true,
        layout: am5.VerticalLayout.new(root, {})
    }));
    const labelText = title === 'Rain' 
        ? `24-Hour Daily Accumulated Rain Chart - ${rainReportType.value === 'Monthly' ? detailRainReport.value.month + ' ' + detailRainReport.value.year : detailRainReport.value.from + ' - ' + detailRainReport.value.to}`
        : title;

    chart.children.unshift(am5.Label.new(root, {
        text: labelText,
        fontSize: 18,
        fontWeight: "600",
        textAlign: "center",
        x: am5.percent(50),
        centerX: am5.percent(50),
        paddingTop: 10,
        paddingBottom: 20
    }));
    let cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
    cursor.lineY.set("visible", false);

    const isClustered = seriesNames.length > 0;

    let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        maxDeviation: 0.3,
        categoryField: "date",
        renderer: am5xy.AxisRendererX.new(root, { 
            minGridDistance: 30,
            minorGridEnabled: true
        }),
        tooltip: am5.Tooltip.new(root, {})
    }));

    // Always rotate and add scrollbar if it's a bar chart or clustered (many items likely)
    if (isBar || isClustered) {
        xAxis.get("renderer").labels.template.setAll({
            rotation: -45,
            centerY: am5.p50,
            centerX: am5.p100,
            paddingRight: 15,
            fontSize: 10
        });
    }

    let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        maxDeviation: 0.3,
        renderer: am5xy.AxisRendererY.new(root, {})
    }));

    if (isClustered) {
        const legend = chart.children.push(am5.Legend.new(root, {
            centerX: am5.p50,
            x: am5.p50,
            paddingTop: 15,
        }));

        // Static mapping based on name to ensure consistency
        const allPossibleNames = Array.from(new Set([
            ...props.sensors.map(s => s.name),
            ...props.stations.map(s => s.name)
        ]));

        seriesNames.forEach((name) => {
            const nameIndex = allPossibleNames.indexOf(name);
            const colorIndex = nameIndex >= 0 ? nameIndex % colors.length : 0;
            const seriesColor = am5.color(colors[colorIndex]);

            const series = chart.series.push(am5xy.ColumnSeries.new(root, {
                name: name,
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: name,
                categoryXField: "date",
                fill: seriesColor,
                stroke: seriesColor,
                tooltip: am5.Tooltip.new(root, {
                    labelText: `{name}: {valueY}${unit}`,
                    getFillFromSprite: false,
                    autoTextColor: false
                })
            }));

            series.columns.template.setAll({
                tooltipText: `{name}: {valueY}${unit}`,
                width: am5.percent(90),
                tooltipY: 0,
                strokeOpacity: 0
            });

            series.bullets.push(function () {
                return am5.Bullet.new(root, {
                    locationY: 0.5,
                    sprite: am5.Label.new(root, {
                        text: "{valueY}" + unit,
                        fill: am5.color(0x000000),
                        centerY: am5.p50,
                        centerX: am5.p50,
                        populateText: true,
                        fontSize: 10,
                        fontWeight: "bold"
                    })
                });
            });

            series.get("tooltip")!.get("background")!.setAll({
                fill: am5.color(0xffffff),
                stroke: seriesColor,
                strokeOpacity: 0.8,
                fillOpacity: 0.9
            });

            series.get("tooltip")!.label.setAll({
                fill: am5.color(0x000000),
                fontSize: 11,
                fontWeight: "600"
            });

            series.data.setAll(data);
            series.appear(1000);
        });

        xAxis.data.setAll(data);
        legend.data.setAll(chart.series.values);
    } else {
        let series: any;
        if (isBar) {
            series = chart.series.push(am5xy.ColumnSeries.new(root, {
                name: title,
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "value",
                categoryXField: "date",
                fill: am5.color(colors[0]),
                stroke: am5.color(colors[0]),
                tooltip: am5.Tooltip.new(root, {
                    labelText: `{valueY}${unit}`
                })
            }));
            
            series.columns.template.setAll({
                tooltipText: `{valueY}${unit}`,
                width: am5.percent(90),
                strokeOpacity: 0
            });

            series.bullets.push(function () {
                return am5.Bullet.new(root, {
                    locationY: 0.5,
                    sprite: am5.Label.new(root, {
                        text: "{valueY}" + unit,
                        fill: am5.color(0x000000),
                        centerY: am5.p50,
                        centerX: am5.p50,
                        populateText: true,
                        fontSize: 10,
                        fontWeight: "bold"
                    })
                });
            });
        } else {
            series = chart.series.push(am5xy.LineSeries.new(root, {
                name: title,
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "value",
                categoryXField: "date",
                stroke: am5.color(colors[0]),
                fill: am5.color(colors[0]),
                tooltip: am5.Tooltip.new(root, {
                    labelText: "{valueY}"
                })
            }));

            series.fills.template.setAll({
                visible: true,
                fillOpacity: 0.5
            });
        }

        xAxis.data.setAll(data);
        series.data.setAll(data);
        series.appear(1000);
    }
    
    chart.appear(1000, 100);

    return root;
}

const initWaterLevelRangeChart = (rootElement: HTMLElement, data: any[], thresholds: any) => {
    const root = setupChartRoot(rootElement);

    const chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: true,
        panY: true,
        wheelX: "panX",
        wheelY: "zoomX",
        pinchZoomX: true,
        layout: am5.VerticalLayout.new(root, {}),
        paddingLeft: 0,
        paddingTop: 10
    }));

    chart.children.unshift(am5.Label.new(root, {
        text: `Monthly Water Level Report (with Daily Fluctuations) - ${waterLevelReportType.value==='Monthly' ? waterLevelReport.value.month+' '+waterLevelReport.value.year : waterLevelReport.value.from+' - '+waterLevelReport.value.to}`,
        fontSize: 18,
        fontWeight: "600",
        textAlign: "center",
        x: am5.percent(50),
        centerX: am5.percent(50),
        paddingTop: 10,
        paddingBottom: 20
    }));

    let cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
    cursor.lineY.set("visible", false);

    let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        maxDeviation: 0.3,
        categoryField: "date",
        renderer: am5xy.AxisRendererX.new(root, { 
            minGridDistance: 30
        }),
        tooltip: am5.Tooltip.new(root, {})
    }));

    xAxis.get("renderer").grid.template.setAll({
        strokeDasharray: [3, 3],
        strokeOpacity: 0.2
    });

    let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        maxDeviation: 0.3,
        renderer: am5xy.AxisRendererY.new(root, {
            pan: "zoom"
        })
    }));

    yAxis.get("renderer").grid.template.setAll({
        strokeDasharray: [3, 3],
        strokeOpacity: 0.2
    });

    yAxis.children.unshift(
        am5.Label.new(root, {
            rotation: -90,
            text: "Water Level (m)",
            y: am5.p50,
            centerX: am5.p50
        })
    );

    // 1. Shaded Area for Min-Max Range
    let rangeSeries = chart.series.push(am5xy.LineSeries.new(root, {
        name: "Daily Range (Min-Max)",
        xAxis: xAxis,
        yAxis: yAxis,
        valueYField: "max",
        openValueYField: "min",
        categoryXField: "date",
        fill: am5.color(0x0a61f4),
        stroke: am5.color(0x0a61f4),
        tooltip: am5.Tooltip.new(root, {
            labelText: "Range: {openValueY}m - {valueY}m"
        })
    }));

    rangeSeries.fills.template.setAll({
        visible: true,
        fillOpacity: 0.2
    });

    rangeSeries.strokes.template.setAll({
        strokeWidth: 0
    });

    // 2. Solid Blue Line for Daily Average
    let avgSeries = chart.series.push(am5xy.LineSeries.new(root, {
        name: "Daily Average",
        xAxis: xAxis,
        yAxis: yAxis,
        valueYField: "avg",
        categoryXField: "date",
        stroke: am5.color(0x0a61f4),
        fill: am5.color(0x0a61f4),
        tooltip: am5.Tooltip.new(root, {
            labelText: "Average: [bold]{valueY}m[/]"
        })
    }));

    avgSeries.strokes.template.setAll({
        strokeWidth: 2
    });

    avgSeries.bullets.push(function() {
        return am5.Bullet.new(root, {
            sprite: am5.Circle.new(root, {
                radius: 4,
                fill: avgSeries.get("fill")
            })
        });
    });

    // 3. Threshold Lines
    if (thresholds) {
        const thresholdColors = [0xffff00, 0xffa500, 0xff5b5b]; // Yellow, Orange, Red
        const thresholdLabels = ["Notice (Level 2)", "Warning (Level 3)", "Critical (Level 4)"];
        const levels = ["level_2", "level_3", "level_4"];

        levels.forEach((level, index) => {
            if (thresholds[level]) {
                let rangeDataItem = yAxis.makeDataItem({
                    value: thresholds[level],
                    endValue: thresholds[level]
                });

                let range = yAxis.createAxisRange(rangeDataItem);

                range.get("grid")?.setAll({
                    stroke: am5.color(thresholdColors[index]),
                    strokeOpacity: 1,
                    strokeDasharray: [4, 4],
                    strokeWidth: 2,
                    visible: true
                });

                range.get("label")?.setAll({
                    text: thresholdLabels[index] + ": " + thresholds[level] + "m",
                    fill: am5.color(thresholdColors[index]),
                    location: 1,
                    fontWeight: "bold",
                    fontSize: 10,
                    centerX: am5.p100,
                    centerY: am5.p100
                });
            }
        });
    }

    let legend = chart.children.push(am5.Legend.new(root, {
        centerX: am5.p0,
        x: am5.p0,
        y: am5.p0,
        paddingLeft: 80,
        layout: root.horizontalLayout
    }));
    
    // Add custom legend item for Threshold if it exists
    if (thresholds && (thresholds.level_2 || thresholds.level_3 || thresholds.level_4)) {
        legend.data.push({
            name: "Critical Threshold",
            color: am5.color(0xff5b5b),
            strokeDasharray: [4, 4],
            icon: am5.Rectangle.new(root, {
                fill: am5.color(0x000000), // dummy icon to be replaced or kept simple
                fillOpacity: 0
            })
        } as any);
    }

    legend.data.setAll(chart.series.values);

    xAxis.data.setAll(data);
    rangeSeries.data.setAll(data);
    avgSeries.data.setAll(data);

    rangeSeries.appear(1000);
    avgSeries.appear(1000);
    chart.appear(1000, 100);

    return root;
}

const processHeatmapData = (records: any[]) => {
    const data: any[] = [];
    const map = new Map();

    records.forEach(record => {
        const date = new Date(record.date_time);
        const day = date.getDate().toString();
        const hour = date.getHours();
        
        // Format hour to "12 AM", "1 AM", etc.
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const h12 = hour % 12 || 12;
        const hourStr = `${h12} ${ampm}`;
        
        // Key for aggregation
        const key = `${day}-${hourStr}`;
        
        if (!map.has(key)) {
            map.set(key, { 
                day, 
                hour: hourStr, 
                sum: 0, 
                count: 0,
                hourVal: hour // for sorting if needed
            });
        }
        
        const entry = map.get(key);
        entry.sum += parseFloat(record.heat_index);
        entry.count++;
    });

    map.forEach(value => {
        data.push({
            day: value.day,
            hour: value.hour,
            value: parseFloat((value.sum / value.count).toFixed(2)),
            hourVal: value.hourVal // Keep for reference if needed, though axes will handle order
        });
    });

    return data;
};


const initHeatmapChart = (root: am5.Root, data: any[], numDays: number = 31) => {
    const chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: false,
        panY: false,
        wheelX: "none",
        wheelY: "none",
        layout: am5.VerticalLayout.new(root, {}),
        paddingRight: 60,
        paddingLeft: 60,
        paddingBottom: 0
    }));

    chart.children.unshift(am5.Label.new(root, {
        text: `Monthly Heat Index Heat Map - ${heatIndexReport.value.station} - ${heatIndexReportType.value === 'Monthly' ? heatIndexReport.value.month+' '+heatIndexReport.value.year : heatIndexReport.value.from + ' - ' + heatIndexReport.value.to}`,
        fontSize: 16,
        fontWeight: "bold",
        textAlign: "center",
        x: am5.percent(50),
        centerX: am5.percent(50),
        paddingTop: 0,
        paddingBottom: 20
    }));
    // Create Axes
    // Y Axis - Hours (12 AM to 11 PM)
    // We want 12 AM at bottom? Or top? Usually time progresses downwards?
    // User spec: 12 AM - 11 PM. I'll do standard bottom-up for now unless chart looks weird.
    // Actually, matrix usually has 0,0 at top-left. Let's try to put 12 AM at top (inverted).
    
    // Sort orders
    const hours = [
        "12 AM", "1 AM", "2 AM", "3 AM", "4 AM", "5 AM", "6 AM", "7 AM", "8 AM", "9 AM", "10 AM", "11 AM",
        "12 PM", "1 PM", "2 PM", "3 PM", "4 PM", "5 PM", "6 PM", "7 PM", "8 PM", "9 PM", "10 PM", "11 PM"
    ];
    
    const yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "hour",
        renderer: am5xy.AxisRendererY.new(root, {
            inversed: true,
            minGridDistance: 20
        }),
        tooltip: am5.Tooltip.new(root, {})
    }));
    
    yAxis.data.setAll(hours.map(h => ({ hour: h })));

    // X Axis - Days
    // Generate days dynamically based on month length
    const days = Array.from({length: numDays}, (_, i) => (i + 1).toString());

    const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "day",
        renderer: am5xy.AxisRendererX.new(root, {
            minGridDistance: 20
        }),
        tooltip: am5.Tooltip.new(root, {})
    }));
    
    xAxis.data.setAll(days.map(d => ({ day: d })));

    // Series
    const series = chart.series.push(am5xy.ColumnSeries.new(root, {
        calculateAggregates: true,
        stroke: am5.color(0xffffff),
        clustered: false,
        xAxis: xAxis,
        yAxis: yAxis,
        categoryXField: "day",
        categoryYField: "hour",
        valueField: "value"
    }));

    series.columns.template.setAll({
        tooltipText: "Day: {day}, Time: {hour}\nHeat Index: [bold]{value}[/]",
        strokeOpacity: 1,
        strokeWidth: 2,
        width: am5.percent(100),
        height: am5.percent(100)
    });

    // Heat Rules (Light Red to Dark Red)
    // Dark Red: 0x8b0000, Light Red/White: 0xffcccc or similar.
    // User said: "Color: Darker red for higher Heat Index."
    series.set("heatRules", [{
        target: series.columns.template,
        min: am5.color(0xffcccc), // Light red/pink
        max: am5.color(0x8b0000), // Dark red
        dataField: "value",
        key: "fill"
    }]);

    series.data.setAll(data);
    series.appear(1000);

    // Add Legend for Heatmap values (Color Key) on the Right
    const heatLegend = chart.children.push(am5.HeatLegend.new(root, {
        orientation: "vertical",
        startColor: am5.color(0xfff7bc),
        endColor: am5.color(0x800026),
        startText: "Low",
        endText: "High",
        stepCount: 5,
        height: am5.percent(100),
        centerY: am5.p50,
        y: am5.p50,
        x: am5.p100,
        centerX: am5.p100,
        paddingTop: -20,
        paddingLeft: 10,
        startOpacity: 1,
        endOpacity: 1
    }));

    chart.appear(1000, 100);

    return root;
};

const processStationHeatmapData = (records: any[]) => {
    const data: any[] = [];
    const map = new Map();

    records.forEach(record => {
        const date = new Date(record.date_time);
        const day = date.getDate().toString();
        const station = record.station_name;
        
        // Key for aggregation
        const key = `${station}-${day}`;
        
        if (!map.has(key)) {
            map.set(key, { 
                station, 
                day, 
                sum: 0, 
                count: 0
            });
        }
        
        const entry = map.get(key);
        entry.sum += parseFloat(record.heat_index);
        entry.count++;
    });

    map.forEach(value => {
        data.push({
            station: value.station,
            day: value.day,
            value: parseFloat((value.sum / value.count).toFixed(2))
        });
    });

    return data;
};

const initStationHeatmapChart = (root: am5.Root, data: any[], stations: any[], numDays: number = 31) => {
    const chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: false,
        panY: false,
        wheelX: "none",
        wheelY: "none",
        height: am5.percent(100),
        layout: am5.VerticalLayout.new(root, {}),
        
        paddingRight: 50,
        paddingLeft: 10,
        paddingBottom: 0,
        paddingTop: 0
    }));
    root.container.setAll({
        paddingBottom: 0,
        paddingTop: 0
    });
    
    // Add Title
    chart.children.unshift(am5.Label.new(root, {
        text: `Monthly Heat Index Comparison Across Devices - ${heatIndexReportType.value === 'Monthly' ? heatIndexReport.value.month + ' ' + heatIndexReport.value.year : heatIndexReport.value.from + ' - ' + heatIndexReport.value.to }`,
        fontSize: 16,
        fontWeight: "bold",
        textAlign: "center",
        x: am5.percent(50),
        centerX: am5.percent(50),
        paddingTop: 60,
        paddingBottom: 20
    }));

    // Y Axis - Stations
    // Collect all unique station names
    const stationNames = stations.map(s => s.name);
    
    // Map colors to stations
    const stationColors: any = {};
    stationNames.forEach((name, i) => {
        stationColors[name] = am5.color(colors[i % colors.length]);
    });

    const yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "station",
        renderer: am5xy.AxisRendererY.new(root, {
            inversed: true,
            minGridDistance: 20
        }),
        tooltip: am5.Tooltip.new(root, {})
    }));
    
    // Show text labels
    yAxis.get("renderer").labels.template.setAll({
        forceHidden: false,
        fontSize: 12
    });

    yAxis.data.setAll(stationNames.map(s => ({ station: s })));

    // X Axis - Days
    const days = Array.from({length: numDays}, (_, i) => (i + 1).toString());

    const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "day",
        renderer: am5xy.AxisRendererX.new(root, {
            minGridDistance: 20
        }),
        tooltip: am5.Tooltip.new(root, {})
    }));
    
    xAxis.data.setAll(days.map(d => ({ day: d })));
    
    // X-Axis Title
    xAxis.children.push(
        am5.Label.new(root, {
            text: "Day of Month",
            x: am5.p50,
            centerX: am5.p50
        })
    );

    // Series
    const series = chart.series.push(am5xy.ColumnSeries.new(root, {
        calculateAggregates: true,
        stroke: am5.color(0xffffff),
        clustered: false,
        xAxis: xAxis,
        yAxis: yAxis,
        categoryXField: "day",
        categoryYField: "station",
        valueField: "value"
    }));

    series.columns.template.setAll({
        tooltipText: "{station}, Day {day}\nHeat Index: [bold]{value}[/]",
        strokeOpacity: 1,
        strokeWidth: 2,
        width: am5.percent(100),
        height: am5.percent(100)
    });
    
    // Add Label to show value inside the cell
    // The user example shows values inside.
    series.bullets.push(function () {
        return am5.Bullet.new(root, {
            locationY: 0.5,
            locationX: 0.5,
            sprite: am5.Label.new(root, {
                text: "{value}",
                fill: am5.color(0x000000), // Black text for contrast on headers, might need adjustment for dark red
                // Logic for contrast: if value is high (dark red), text should technically be white.
                // But user example has black text on light cells. 
                // Let's stick to black or auto. 
                // amCharts autoTextColor might work if background is set. 
                // But column fill is set via heatRules.
                // explicitly setting to black for now as per user request "text color black" in previous turn.
                centerY: am5.p50,
                centerX: am5.p50,
                populateText: true,
                fontSize: 10
            })
        });
    });

    // Heat Rules (Light Yellow to Dark Red as per user example image which looks like spectral/heat)
    // User text said: "Color: Darker red for higher Heat Index." 
    // User image shows: Light Yellow -> Orange -> Dark Red.
    // Let's try to match that gradient.
    series.set("heatRules", [{
        target: series.columns.template,
        min: am5.color(0xfff7bc), // Light yellow
        max: am5.color(0x800026), // Dark red
        dataField: "value",
        key: "fill"
    }]);

    series.data.setAll(data);
    series.appear(1000);
    
    // Add Legend for Heatmap values (Color Key) on the Right
    const heatLegend = chart.children.push(am5.HeatLegend.new(root, {
        orientation: "vertical",
        startColor: am5.color(0xfff7bc),
        endColor: am5.color(0x800026),
        startText: "Low",
        endText: "High",
        stepCount: 5,
        height: am5.percent(100),
        centerY: am5.p50,
        y: am5.p50,
        x: am5.p100,
        centerX: am5.p100,
        paddingLeft: 15,
        startOpacity: 1,
        endOpacity: 1
    }));

    heatLegend.startLabel.setAll({
        fontSize: 12,
        fill: heatLegend.get("startColor")
    });

    heatLegend.endLabel.setAll({
        fontSize: 12,
        fill: heatLegend.get("endColor")
    });

    chart.appear(1000, 100);

    return root;
};

const renderChart = async (chartData?: any[], seriesNames: string[] = []) => {
    await nextTick(); // Wait for v-if DOM updates to ensure refs are available

    // Dispose existing chart if any to prevent memory leaks and conflicts
    if (activeChartRoot) {
        try {
            activeChartRoot.dispose();
        } catch (e) {
            console.warn("Error disposing chart root:", e);
        }
        activeChartRoot = null;
    }

    // Determine target element based on report type
    let chartDiv: HTMLElement | null = null;
    let title = '';
    let unit = '';
    
    if (selectedReport.value === 'Rain') {
        chartDiv = rainChartDiv.value;
        title = `24-Hour Daily Accumulated Rain Chart - ${rainReportType.value === 'Monthly' ? detailRainReport.value.month + ' ' + detailRainReport.value.year : detailRainReport.value.from + ' - ' + detailRainReport.value.to}`;
        unit = 'mm';
    } else if (selectedReport.value === 'Heat Index') {
        chartDiv = heatIndexChartDiv.value;
        title = 'Heat Index';
        unit = '°C';
        
        // Special handling for Heat Index Heatmap
        if (chartDiv && heatIndexRecords.value.length > 0) {
            const root = am5.Root.new(chartDiv);
            activeChartRoot = root;
            root.setThemes([am5themes_Animated.new(root)]);
            
            // Determine number of days in the selected month
            const numDays = getDaysInMonth(heatIndexReport.value.month, parseInt(heatIndexReport.value.year));

            // Check if "All Stations" is selected for Heat Index
            if (heatIndexReport.value.station === 'All') {
                const heatmapData = processStationHeatmapData(heatIndexRecords.value);
                initStationHeatmapChart(root, heatmapData, props.stations, numDays);
            } else {
                // Default Time-based Heatmap (Hours x Days)
                const heatmapData = processHeatmapData(heatIndexRecords.value);
                initHeatmapChart(root, heatmapData, numDays);
            }
            return;
        }

    } else if (selectedReport.value === 'Water Level') {
        chartDiv = waterLevelChartDiv.value;
        title = 'Water Level';
        unit = 'm';

        if (chartDiv && waterLevelRecords.value.length > 0 && waterLevelReport.value.sensor !== 'All') {
            activeChartRoot = initWaterLevelRangeChart(chartDiv, chartData || [], waterLevelThresholds.value);
            return;
        }
    }

    if (!chartData || chartData.length === 0) {
        return;
    }

    if (chartDiv) {
        activeChartRoot = initChart(chartDiv, title, chartData, seriesNames, true, unit);
    }
};

const clearCharts = () => {
    if (activeChartRoot) {
        try {
            activeChartRoot.dispose();
        } catch (e) {
            console.warn("Error disposing chart:", e);
        }
        activeChartRoot = null;
    }
};

const handleWaterLevelReport = async () => {
    const response = await axios.get('/reports/water-level-data', {
        params: {
            sensor: waterLevelReport.value.sensor,
            reportType: waterLevelReportType.value,
            year: waterLevelReport.value.year,
            month: waterLevelReport.value.month,
            from: waterLevelReport.value.from,
            to: waterLevelReport.value.to
        }
    });
    
    waterLevelRecords.value = response.data.records;
    waterLevelSummaryRecords.value = response.data.summaryRecords || [];
    waterLevelThresholds.value = response.data.thresholds;
    waterLevelActiveTab.value = waterLevelReport.value.sensor === 'All' ? 'Summary' : '';
    
    if (waterLevelReport.value.sensor !== 'All') {
        const dailyData = waterLevelRecords.value.reduce((acc: any, record: any) => {
            const dateObj = new Date(record.date_time);
            const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            
            if (!acc[dateStr]) {
                acc[dateStr] = { min: record.water_level, max: record.water_level, sum: 0, count: 0, timestamp: dateObj.getTime() };
            }
            
            acc[dateStr].min = Math.min(acc[dateStr].min, record.water_level);
            acc[dateStr].max = Math.max(acc[dateStr].max, record.water_level);
            acc[dateStr].sum += record.water_level;
            acc[dateStr].count++;
            return acc;
        }, {});

        const chartData = Object.entries(dailyData).map(([date, vals]: [string, any]) => ({
            date,
            min: vals.min,
            max: vals.max,
            avg: parseFloat((vals.sum / vals.count).toFixed(2)),
            timestamp: vals.timestamp
        })).sort((a, b) => a.timestamp - b.timestamp);

        await renderChart(chartData);
        return;
    }

    // legacy comparison view for "All"
    const sensorNames = props.sensors.map(s => s.name);
    const timestampDataRaw = waterLevelRecords.value.reduce((acc: any, record: any) => {
        const dateObj = new Date(record.date_time);
        const label = waterLevelReportType.value === 'Monthly' 
            ? dateObj.toLocaleString('en-US', { day: '2-digit' })
            : dateObj.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
        
        if (!acc[label]) acc[label] = { sensors: {}, timestamp: dateObj.getTime() };
        
        if (!acc[label].sensors[record.sensor_name]) {
            acc[label].sensors[record.sensor_name] = { sum: 0, count: 0 };
        }
        acc[label].sensors[record.sensor_name].sum += record.water_level;
        acc[label].sensors[record.sensor_name].count += 1;
        return acc;
    }, {});

    const chartData = Object.entries(timestampDataRaw).map(([label, data]: [string, any]) => {
        const row: any = { date: label, timestamp: data.timestamp };
        sensorNames.forEach(name => {
            if (data.sensors[name]) {
                row[name] = data.sensors[name].sum / data.sensors[name].count;
            }
        });
        return row;
    }).sort((a, b) => a.timestamp - b.timestamp);

    await renderChart(chartData, sensorNames);
};

const handleWeatherReport = async () => {
    const isRain = selectedReport.value === 'Rain';
    const filters = isRain ? detailRainReport.value : heatIndexReport.value;
    const reportType = isRain ? rainReportType.value : heatIndexReportType.value;

    const response = await axios.get('/reports/weather-observation-data', {
        params: {
            report: selectedReport.value,
            station: filters.station,
            reportType: reportType,
            year: filters.year,
            month: filters.month,
            from: filters.from,
            to: filters.to
        }
    });
    
    if (isRain) {
        rainRecords.value = response.data.records;
        rainSummaryRecords.value = response.data.summaryRecords || [];
        rainActiveTab.value = detailRainReport.value.station === 'All' ? 'Summary' : '';
        
        let chartData = response.data.chartData || [];
        if (rainReportType.value === 'Monthly') {
            chartData = chartData.map((item: any) => ({
                ...item,
                date: item.date.split(' ')[1] || item.date
            }));
        }
        await renderChart(chartData, response.data.stationNames);
    } else {
        heatIndexRecords.value = response.data.records;
        heatIndexSummaryRecords.value = response.data.summaryRecords || [];
        heatIndexActiveTab.value = heatIndexReport.value.station === 'All' ? 'Summary' : '';
        await renderChart();
    }
};

const generateReport = async () => {
    clearCharts();
    isGenerating.value = true;
    rainActiveTab.value = '';
    heatIndexActiveTab.value = '';
    waterLevelActiveTab.value = '';
    currentPage.value = 1;
    hasSearched.value = true;

    try {
        if (selectedReport.value === 'Water Level') {
            await handleWaterLevelReport();
        } else if (selectedReport.value === 'Rain' || selectedReport.value === 'Heat Index') {
            await handleWeatherReport();
        } else {
            await renderChart();
        }
    } catch (error) {
        console.error(`Error generating ${selectedReport.value} report:`, error);
        window.dispatchEvent(new CustomEvent('toast', { 
            detail: { 
                message: `Failed to generate ${selectedReport.value} report. Please try again.`, 
                type: 'error' 
            } 
        }));
    } finally {
        isGenerating.value = false;
    }
};

watch(selectedReport, () => {
    rainRecords.value = [];
    heatIndexRecords.value = [];
    waterLevelRecords.value = [];
    clearCharts();
    currentPage.value = 1;
    hasSearched.value = false;
    rainActiveTab.value = '';
    heatIndexActiveTab.value = '';
    waterLevelActiveTab.value = '';
});

onMounted(() => {
    const currentYear = new Date().getFullYear();
    if (props.weatherStationYears.includes(currentYear)) {
        detailRainReport.value.year = currentYear.toString();
        heatIndexReport.value.year = currentYear.toString();
    }
    if (props.waterLevelYears.includes(currentYear)) {
        waterLevelReport.value.year = currentYear.toString();
    }
});

onUnmounted(() => {
    if (activeChartRoot) {
        activeChartRoot.dispose();
    }
});

const currentDate = new Date().toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
});

const exportToExcel = () => {
    let records: any[] = [];
    let filenamePrefix = '';
    let sheetName = '';
    let headers: any = {};

    if (selectedReport.value === 'Water Level') {
        records = waterLevelRecords.value;
        filenamePrefix = 'Water_Level_Data_Report';
        sheetName = 'Water Level Data';
        headers = (record: any, index: number) => ({
            'No.': index + 1,
            'Sensor Name': record.sensor_name,
            'Water Level (m)': record.water_level,
            'Date & Time': new Date(record.date_time).toLocaleString('en-US', { 
                month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' 
            })
        });
    } else if (selectedReport.value === 'Rain') {
        records = rainRecords.value;
        filenamePrefix = 'Rain_Data_Report';
        sheetName = 'Rain Data';
        headers = (record: any, index: number) => ({
            'No.': index + 1,
            'Station Name': record.station_name,
            'Precipitation Rate (mm/h)': record.precipitation_rate,
            'Precipitation Total (mm)': record.precipitation_total,
            'Date & Time': new Date(record.date_time).toLocaleString('en-US', { 
                month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' 
            })
        });
    } else if (selectedReport.value === 'Heat Index') {
        records = heatIndexRecords.value;
        filenamePrefix = 'Heat_Index_Data_Report';
        sheetName = 'Heat Index Data';
        headers = (record: any, index: number) => ({
            'No.': index + 1,
            'Station Name': record.station_name,
            'Temperature (°C)': record.temperature,
            'Humidity (%)': record.humidity,
            'Dewpoint (°C)': record.dewpoint,
            'Heat Index (°C)': record.heat_index,
            'Date & Time': new Date(record.date_time).toLocaleString('en-US', { 
                month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' 
            })
        });
    }

    if (records.length === 0) return;

    const workbook = XLSX.utils.book_new();

    const isAll = (selectedReport.value === 'Water Level' && waterLevelReport.value.sensor === 'All') ||
                  (selectedReport.value === 'Rain' && detailRainReport.value.station === 'All') ||
                  (selectedReport.value === 'Heat Index' && heatIndexReport.value.station === 'All');

    if (isAll) {
        const groupKey = selectedReport.value === 'Water Level' ? 'sensor_name' : 'station_name';
        const groups = records.reduce((acc: any, record: any) => {
            const key = record[groupKey] || 'Unknown';
            if (!acc[key]) acc[key] = [];
            acc[key].push(record);
            return acc;
        }, {});

        Object.entries(groups).forEach(([name, groupRecords]: [string, any]) => {
            const exportData = groupRecords.map((record: any, index: number) => headers(record, index));
            const worksheet = XLSX.utils.json_to_sheet(exportData);
            // Excel sheet names: max 31 chars, no invalid chars: \ / ? * [ ]
            const safeName = name.substring(0, 31).replace(/[\[\]\*\?\/\\]/g, '');
            XLSX.utils.book_append_sheet(workbook, worksheet, safeName || 'Data');
        });
    } else {
        const exportData = records.map((record, index) => headers(record, index));
        const worksheet = XLSX.utils.json_to_sheet(exportData);
        XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
    }

    // Dynamic Suffix based on selection
    let suffix = '';
    if (selectedReport.value === 'Water Level') {
        if (waterLevelReportType.value === 'Monthly') {
            suffix = `${waterLevelReport.value.month}_${waterLevelReport.value.year}`;
        } else {
            suffix = `${waterLevelReport.value.from}_to_${waterLevelReport.value.to}`;
        }
    } else if (selectedReport.value === 'Rain') {
        if (rainReportType.value === 'Monthly') {
            suffix = `${detailRainReport.value.month}_${detailRainReport.value.year}`;
        } else {
            suffix = `${detailRainReport.value.from}_to_${detailRainReport.value.to}`;
        }
    } else if (selectedReport.value === 'Heat Index') {
        if (heatIndexReportType.value === 'Monthly') {
            suffix = `${heatIndexReport.value.month}_${heatIndexReport.value.year}`;
        } else {
            suffix = `${heatIndexReport.value.from}_to_${heatIndexReport.value.to}`;
        }
    }

    const filename = `${filenamePrefix}_${suffix}.xlsx`;

    XLSX.writeFile(workbook, filename);
};

const exportSummaryToExcel = () => {
    let matrix: any = null;
    let filenamePrefix = '';
    let sheetName = '';
    let suffix = '';

    if (selectedReport.value === 'Rain') {
        matrix = rainSummaryMatrix.value;
        filenamePrefix = 'Rain_Summary_Report';
        sheetName = 'Rain Summary';
        suffix = rainReportType.value === 'Monthly' 
            ? `${detailRainReport.value.month}_${detailRainReport.value.year}` 
            : `${detailRainReport.value.from}_to_${detailRainReport.value.to}`;
    } else if (selectedReport.value === 'Heat Index') {
        matrix = heatIndexSummaryMatrix.value;
        filenamePrefix = 'Heat_Index_Summary_Report';
        sheetName = 'Heat Index Summary';
        suffix = heatIndexReportType.value === 'Monthly' 
            ? `${heatIndexReport.value.month}_${heatIndexReport.value.year}` 
            : `${heatIndexReport.value.from}_to_${heatIndexReport.value.to}`;
    } else if (selectedReport.value === 'Water Level') {
        matrix = waterLevelSummaryMatrix.value;
        filenamePrefix = 'Water_Level_Summary_Report';
        sheetName = 'Water Level Summary';
        suffix = waterLevelReportType.value === 'Monthly' 
            ? `${waterLevelReport.value.month}_${waterLevelReport.value.year}` 
            : `${waterLevelReport.value.from}_to_${waterLevelReport.value.to}`;
    }

    if (!matrix) return;

    const devices = matrix.stations || matrix.sensors;
    const rows = matrix.rows.map((row: any) => {
        const rowData: any = { 'Date': row.dateLabel };
        devices.forEach((device: string) => {
            rowData[device] = row[device];
        });
        return rowData;
    });

    // Add Totals/Averages for Rain
    if (selectedReport.value === 'Rain') {
        const totalRow: any = { 'Date': 'Total rainfall (mm)' };
        const averageRow: any = { 'Date': 'Ave. rainfall/day' };
        devices.forEach((device: string) => {
            totalRow[device] = matrix.totals[device];
            averageRow[device] = matrix.averages[device];
        });
        rows.push({}); // Empty row
        rows.push(totalRow);
        rows.push(averageRow);
    } else {
        // Averages for Heat Index and Water Level
        const label = selectedReport.value === 'Heat Index' ? 'Ave. Heat Index/day' : 'Ave. Water Level/day';
        const averageRow: any = { 'Date': label };
        devices.forEach((device: string) => {
            averageRow[device] = matrix.averages[device];
        });
        rows.push({}); // Empty row
        rows.push(averageRow);
    }

    const worksheet = XLSX.utils.json_to_sheet(rows);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);

    const filename = `${filenamePrefix}_${suffix}.xlsx`;
    XLSX.writeFile(workbook, filename);
};

const downloadChart = () => { //CHART to b64 to PNG IMAGE DL
    console.log("Download chart clicked. Active root:", activeChartRoot);
    if (activeChartRoot) {
        try {
            // Generate filename suffix based on report type/date
            let suffix = '';
            if (selectedReport.value === 'Water Level') {
                if (waterLevelReportType.value === 'Monthly') {
                    suffix = `${waterLevelReport.value.month}_${waterLevelReport.value.year}`;
                } else {
                    suffix = `${waterLevelReport.value.from}_to_${waterLevelReport.value.to}`;
                }
            } else if (selectedReport.value === 'Rain') {
                if (rainReportType.value === 'Monthly') {
                    suffix = `${detailRainReport.value.month}_${detailRainReport.value.year}`;
                } else {
                    suffix = `${detailRainReport.value.from}_to_${detailRainReport.value.to}`;
                }
            } else if (selectedReport.value === 'Heat Index') {
                if (heatIndexReportType.value === 'Monthly') {
                    suffix = `${heatIndexReport.value.month}_${heatIndexReport.value.year}`;
                } else {
                    suffix = `${heatIndexReport.value.from}_to_${heatIndexReport.value.to}`;
                }
            }

            let filePrefix = `${selectedReport.value.replace(/[\s()]+/g, '_')}_Chart_${suffix}`;
            if (selectedReport.value === 'Rain') {
                filePrefix = `24-Hour_Daily_Accumulated_Rain_Chart_${suffix}`;
            }

            const exporting = Exporting.new(activeChartRoot, {
                filePrefix: filePrefix,
                pdfOptions: {
                    includeData: true
                },
                pngOptions: {
                    quality: 0.8,
                    maintainPixelRatio: true
                }
            });
            console.log("Exporting instance created:", exporting);
            
            // Export to PNG with high quality
            exporting.export("png").then((response: any) => {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { message: "Export successful, initiating download...", type: 'success' } 
                }));
                const link = document.createElement("a");
                link.href = response;
                link.download = `${exporting.get("filePrefix")}.png`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }).catch((error: any) => {
                console.error("Export failed:", error);
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { message: "Failed to export chart. Please check console for details.", type: 'error' } 
                }));
            });
        } catch (e: any) {
            console.error("Error creating Exporting instance:", e);
            window.dispatchEvent(new CustomEvent('toast', { 
                detail: { message: "Error creating chart export component.", type: 'error' } 
            }));
        }
    } else {
        console.warn("No active chart root found.");
        window.dispatchEvent(new CustomEvent('toast', { 
            detail: { message: "Chart is not ready for export yet.", type: 'error' } 
        }));
    }
};

</script>

<template>
    <AppLayout title="Reports">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-3xl text-gray-800 uppercase tracking-wide">
                    Reports
                </h2>
                <div class="text-gray-400 text-sm">
                    Today is {{ currentDate }}
                </div>
            </div>
        </template>

        <div class="pt-0 mb-16">
            <div class="w-full space-y-12">
                <div class="bg-gray-200/[0.25] p-8 pt-2 mb-6 h-full">
                    <!-- Report Selection Header -->
                     <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2">
                        <div class="flex items-center space-x-4">
                            <h3 class="text-xl font-bold text-gray-800 uppercase">
                                Select Report
                            </h3>
                            <p class="text-sm text-gray-400 italic opacity-70">
                                Choose which report you would like to generate
                            </p>
                        </div>
                        <select v-model="selectedReport" class="w-80 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                            <option v-for="type in reportTypes" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>

                <!-- Rain Report -->
                    <div v-if="selectedReport === 'Rain'" class="space-y-4">
                        <h3 class="text-xl font-bold text-gray-800 uppercase flex items-center gap-4">
                            Weather Observation (<span class="italic">Rain</span>)
                            <span class="text-sm font-normal italic text-gray-600 opacity-50 capitalize">Default interval is 24 Hours and Observation data with 0s for both rate & total are ommitted</span>
                        </h3>
                        <div class="flex flex-wrap gap-4 items-center">
                            <select v-model="rainReportType" class="w-48 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                <option value="Monthly">Monthly</option>
                                <option value="Date Range">Date Range</option>
                            </select>

                            <template v-if="rainReportType === 'Monthly'">
                                <select v-model="detailRainReport.month" class="w-48 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                    <option value="" disabled selected>SELECT MONTH</option>
                                    <option v-for="m in months" :key="m" :value="m">{{ m }}</option>
                                </select>

                                <select v-model="detailRainReport.year" class="w-48 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                    <option value="" disabled selected>SELECT YEAR</option>
                                    <option v-for="y in weatherStationYears" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </template>
                            
                            <template v-else>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400 font-bold text-sm uppercase">FROM</span>
                                    <div class="relative w-48 h-[50px] bg-white border border-gray-200 rounded-sm">
                                        <span class="absolute left-3 top-1 text-black font-bold text-[10px] uppercase pointer-events-none z-10">DATE</span>
                                        <input type="date" v-model="detailRainReport.from" class="w-full h-full bg-transparent border-none text-gray-500 text-sm focus:ring-0 block px-2.5 pt-4 font-bold" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400 font-bold text-sm uppercase">TO</span>
                                    <div class="relative w-48 h-[50px] bg-white border border-gray-200 rounded-sm">
                                        <span class="absolute left-3 top-1 text-black font-bold text-[10px] uppercase pointer-events-none z-10">DATE</span>
                                        <input type="date" v-model="detailRainReport.to" class="w-full h-full bg-transparent border-none text-gray-500 text-sm focus:ring-0 block px-2.5 pt-4 font-bold" />
                                    </div>
                                </div>
                            </template>
                            <select v-model="detailRainReport.station" class="w-64 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                <option value="" disabled selected>SELECT STATION</option>
                                <option v-for="station in stations" :key="station.id" :value="station.name">{{ station.name }}</option>
                                <option value="All">All Stations</option>
                            </select>

                            <button 
                                @click="generateReport" 
                                :disabled="isGenerating"
                                class="bg-blue-900 hover:bg-blue-800 disabled:bg-gray-400 text-white px-8 py-2.5 rounded-sm font-bold uppercase tracking-wider text-sm transition-colors flex items-center gap-2"
                            >
                                <span v-if="isGenerating" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                {{ isGenerating ? 'Generating...' : 'Generate' }}
                            </button>
                        </div>
                    </div>

                    <!-- Heat Index Report -->
                    <div v-if="selectedReport === 'Heat Index'" class="space-y-4">
                        <h3 class="text-xl font-bold text-gray-800 uppercase flex items-center gap-4">
                            Weather Observation (<span class="italic">Heat Index</span>)
                            <span class="text-sm font-normal italic text-gray-600 opacity-50 capitalize">Data displayed on a daily basis from all devices</span>
                        </h3>
                        <div class="flex flex-wrap gap-4 items-center">
                            <select v-model="heatIndexReportType" class="w-48 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                <option value="Monthly">Monthly</option>
                                <option value="Date Range">Date Range</option>
                            </select>

                            <template v-if="heatIndexReportType === 'Monthly'">
                                <select v-model="heatIndexReport.month" class="w-64 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                    <option value="" disabled selected>SELECT MONTH</option>
                                    <option v-for="m in months" :key="m" :value="m">{{ m }}</option>
                                </select>

                                <select v-model="heatIndexReport.year" class="w-64 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                    <option value="" disabled selected>SELECT YEAR</option>
                                    <option v-for="y in weatherStationYears" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </template>

                            <template v-else>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400 font-bold text-sm uppercase">FROM</span>
                                    <div class="relative w-48 h-[50px] bg-white border border-gray-200 rounded-sm">
                                        <span class="absolute left-3 top-1 text-black font-bold text-[10px] uppercase pointer-events-none z-10">DATE</span>
                                        <input type="date" v-model="heatIndexReport.from" class="w-full h-full bg-transparent border-none text-gray-500 text-sm focus:ring-0 block px-2.5 pt-4 font-bold" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400 font-bold text-sm uppercase">TO</span>
                                    <div class="relative w-48 h-[50px] bg-white border border-gray-200 rounded-sm">
                                        <span class="absolute left-3 top-1 text-black font-bold text-[10px] uppercase pointer-events-none z-10">DATE</span>
                                        <input type="date" v-model="heatIndexReport.to" class="w-full h-full bg-transparent border-none text-gray-500 text-sm focus:ring-0 block px-2.5 pt-4 font-bold" />
                                    </div>
                                </div>
                            </template>

                            <select v-model="heatIndexReport.station" class="w-64 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                <option value="" disabled selected>SELECT STATION</option>
                                <option v-for="station in stations" :key="station.id" :value="station.name">{{ station.name }}</option>
                                <option value="All">All Stations</option>
                            </select>

                            <button 
                                @click="generateReport" 
                                :disabled="isGenerating"
                                class="bg-blue-900 hover:bg-blue-800 disabled:bg-gray-400 text-white px-8 py-2.5 rounded-sm font-bold uppercase tracking-wider text-sm transition-colors flex items-center gap-2"
                            >
                                <span v-if="isGenerating" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                {{ isGenerating ? 'Generating...' : 'Generate' }}
                            </button>
                        </div>
                    </div>

                    <!-- Water Level Sensor Data Report -->
                    <div v-if="selectedReport === 'Water Level'" class="space-y-4">
                        <h3 class="text-xl font-bold text-gray-800 uppercase flex items-center gap-4">
                            Water Level Sensors
                            <span class="text-sm font-normal italic text-gray-600 opacity-50 capitalize">Select sensor and date range</span>
                        </h3>
                        <div class="flex flex-wrap gap-4 items-center">
                            <select v-model="waterLevelReportType" class="w-48 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                <option value="Monthly">Monthly</option>
                                <option value="Date Range">Date Range</option>
                            </select>

                            <template v-if="waterLevelReportType === 'Monthly'">
                                <select v-model="waterLevelReport.month" class="w-48 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                    <option value="" disabled selected>SELECT MONTH</option>
                                    <option v-for="m in months" :key="m" :value="m">{{ m }}</option>
                                </select>

                                  <select v-model="waterLevelReport.year" class="w-48 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                    <option value="" disabled selected>SELECT YEAR</option>
                                    <option v-for="y in waterLevelYears" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </template>

                            <template v-else>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400 font-bold text-sm uppercase">FROM</span>
                                    <div class="relative w-48 h-[50px] bg-white border border-gray-200 rounded-sm">
                                        <span class="absolute left-3 top-1 text-black font-bold text-[10px] uppercase pointer-events-none z-10">DATE</span>
                                        <input type="date" v-model="waterLevelReport.from" class="w-full h-full bg-transparent border-none text-gray-500 text-sm focus:ring-0 block px-2.5 pt-4 font-bold" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400 font-bold text-sm uppercase">TO</span>
                                    <div class="relative w-48 h-[50px] bg-white border border-gray-200 rounded-sm">
                                        <span class="absolute left-3 top-1 text-black font-bold text-[10px] uppercase pointer-events-none z-10">DATE</span>
                                        <input type="date" v-model="waterLevelReport.to" class="w-full h-full bg-transparent border-none text-gray-500 text-sm focus:ring-0 block px-2.5 pt-4 font-bold" />
                                    </div>
                                </div>
                            </template>
                            
                            <select v-model="waterLevelReport.sensor" class="w-64 uppercase bg-white border border-gray-200 text-gray-800 text-sm rounded-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-bold tracking-wider">
                                <option value="" disabled selected>SELECT SENSOR</option>
                                <option v-for="sensor in sensors" :key="sensor.id" :value="sensor.name">{{ sensor.name }}</option>
                                <option value="All">All Sensors</option>
                            </select>


                            <button 
                                @click="generateReport" 
                                :disabled="isGenerating"
                                class="bg-blue-900 hover:bg-blue-800 disabled:bg-gray-400 text-white px-8 py-2.5 rounded-sm font-bold uppercase tracking-wider text-sm transition-colors flex items-center gap-2"
                            >
                                <span v-if="isGenerating" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                {{ isGenerating ? 'Generating...' : 'Generate' }}
                            </button>
                        </div>
                    </div>
                    
                    <!-- Graphical Reports -->
                    <div v-if="rainRecords.length > 0 || heatIndexRecords.length > 0 || waterLevelRecords.length > 0" class="space-y-8 pt-8 mb-12">
                        <!-- Rain Chart -->
                        <div v-show="selectedReport === 'Rain' && rainRecords.length > 0" class="space-y-2">
                            <div class="flex justify-between items-center">
                                <h4 class="font-bold text-gray-600 uppercase text-sm hidden">24-Hour Daily Accumulated Rain Chart - {{ rainReportType==='Monthly'? detailRainReport.month + ' ' + detailRainReport.year : detailRainReport.from + ' ' + detailRainReport.to }}</h4>
                                <button 
                                    @click="downloadChart" 
                                    class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download Chart
                                </button>
                            </div>
                            <div class="bg-white p-2 border-2 border-orange-500 rounded-2xl shadow-md h-[600px] w-full" ref="rainChartDiv"></div>
                            <!-- <div class="text-center text-xs font-bold text-gray-400 uppercase">Month</div> -->

                            <!-- Rain Results Table -->
                            <div v-if="rainRecords.length > 0" class="bg-white  border-2 border-orange-500 rounded-2xl shadow-md overflow-hidden mt-8">
                                <div class="flex justify-between items-center py-2 px-4 border-b border-gray-100">
                                    <h4 class="font-bold text-gray-600 uppercase text-sm">Weather Observation (Rain) Data Table</h4>
                                    <div class="flex gap-2">
                                        <button 
                                            v-if="(rainActiveTab || rainTabs[0]) === 'Summary'"
                                            @click="exportSummaryToExcel" 
                                            class="bg-green-700 hover:bg-green-600 text-white px-4 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Export Summary
                                        </button>
                                        <button 
                                            v-else
                                            @click="exportToExcel" 
                                            class="bg-green-700 hover:bg-green-600 text-white px-4 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Export Data
                                        </button>
                                    </div>
                                </div>

                                <!-- Rain Tab Navigation -->
                                <div v-if="detailRainReport.station === 'All' && rainTabs.length > 1" class="flex flex-wrap gap-2 border-b border-gray-100 px-4 pt-2 bg-gray-50/30">
                                    <button 
                                        v-for="tab in rainTabs" 
                                        :key="tab"
                                        @click="rainActiveTab = tab; currentPage = 1"
                                        :class="[
                                            'px-6 py-2.5 text-[10px] font-bold uppercase tracking-widest transition-all relative',
                                            (rainActiveTab || rainTabs[0]) === tab 
                                                ? 'text-blue-600' 
                                                : 'text-gray-400 hover:text-gray-600'
                                        ]"
                                    >
                                        {{ tab }}
                                        <div v-if="(rainActiveTab || rainTabs[0]) === tab" class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600"></div>
                                    </button>
                                </div>

                                <!-- Top Pagination Controls -->
                                <div v-if="rainTotalPages > 1" class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ rainTotalPages }}</span>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase tracking-widest">({{ filteredRainRecords.length }} records)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="currentPage--" 
                                            :disabled="currentPage === 1"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="currentPage++" 
                                            :disabled="currentPage === rainTotalPages"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Rain Matrix View for Summary -->
                                <div v-if="(rainActiveTab || rainTabs[0]) === 'Summary' && rainSummaryMatrix" class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="bg-gray-100 border-b border-gray-200">
                                            <tr>
                                                <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase border-r border-gray-200">Date</th>
                                                <th v-for="station in rainSummaryMatrix.stations" :key="station" class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center border-r border-gray-200">
                                                    {{ station }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(row, idx) in rainSummaryMatrix.rows" :key="idx" class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-2 text-sm text-gray-500 font-bold border-r border-gray-200">{{ row.dateLabel }}</td>
                                                <td v-for="station in rainSummaryMatrix.stations" :key="station" class="px-4 py-2 text-sm text-gray-800 font-bold text-center border-r border-gray-200">
                                                    {{ row[station] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                            <tr class="font-bold">
                                                <td class="px-4 py-3 text-[10px] text-gray-600 uppercase border-r border-gray-200">Total rainfall (mm)</td>
                                                <td v-for="station in rainSummaryMatrix.stations" :key="station" class="px-4 py-3 text-sm text-blue-700 text-center border-r border-gray-200">
                                                    {{ rainSummaryMatrix.totals[station] }}
                                                </td>
                                            </tr>
                                            <tr class="font-bold">
                                                <td class="px-4 py-3 text-[10px] text-gray-600 uppercase border-r border-gray-200">Ave. rainfall/day</td>
                                                <td v-for="station in rainSummaryMatrix.stations" :key="station" class="px-4 py-3 text-sm text-green-700 text-center border-r border-gray-200">
                                                    {{ rainSummaryMatrix.averages[station] }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <table v-else class="w-full text-left">
                                    <thead class="bg-gray-100 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase">No.</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase">Station Name</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Rate (mm/h)</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Total (mm)</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(record, index) in paginatedRainRecords" :key="index" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-500 font-bold font-mono">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold uppercase tracking-wider">{{ record.station_name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold text-center">{{ record.precipitation_rate }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold text-center">{{ record.precipitation_total }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 font-bold text-center">
                                                {{ new Date(record.date_time).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Pagination Controls -->
                                <div v-if="(rainActiveTab || rainTabs[0]) !== 'Summary' && rainTotalPages > 1" class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ rainTotalPages }}</span>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase tracking-widest">({{ filteredRainRecords.length }} records)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="currentPage--" 
                                            :disabled="currentPage === 1"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="currentPage++" 
                                            :disabled="currentPage === rainTotalPages"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Heat Index Chart -->
                        <div v-show="selectedReport === 'Heat Index' && heatIndexRecords.length > 0" class="space-y-6">
                            <div class="flex justify-between items-center">
                                <h4 class="font-bold text-gray-600 uppercase text-sm hidden">Heat Index</h4>
                                <button 
                                    @click="downloadChart" 
                                    class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download Chart
                                </button>
                            </div>
                            <div class="bg-white p-2 rounded-sm border border-gray-100 w-full" :class="heatIndexReport.station === 'All' ? 'h-[450px]' : 'h-[500px]'" ref="heatIndexChartDiv"></div>
                            <!-- <div class="text-center text-xs font-bold text-gray-400 uppercase">Month</div> -->

                            <!-- Heat Index Results Table -->
                            <div v-if="heatIndexRecords.length > 0" class="bg-white border border-gray-200 rounded-sm overflow-hidden mt-8">
                                <div class="flex justify-between items-center py-2 px-4 border-b border-gray-100">
                                    <h4 class="font-bold text-gray-600 uppercase text-sm">Heat Index Data Table</h4>
                                    <div class="flex gap-2">
                                        <button 
                                            v-if="(heatIndexActiveTab || heatIndexTabs[0]) === 'Summary'"
                                            @click="exportSummaryToExcel" 
                                            class="bg-green-700 hover:bg-green-600 text-white px-4 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Export Summary
                                        </button>
                                        <button 
                                            v-else
                                            @click="exportToExcel" 
                                            class="bg-green-700 hover:bg-green-600 text-white px-4 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Export Data
                                        </button>
                                    </div>
                                </div>

                                <!-- Heat Index Tab Navigation -->
                                <div v-if="heatIndexReport.station === 'All' && heatIndexTabs.length > 1" class="flex flex-wrap gap-2 border-b border-gray-100 px-4 pt-2 bg-gray-50/30">
                                    <button 
                                        v-for="tab in heatIndexTabs" 
                                        :key="tab"
                                        @click="heatIndexActiveTab = tab; currentPage = 1"
                                        :class="[
                                            'px-6 py-2.5 text-[10px] font-bold uppercase tracking-widest transition-all relative',
                                            (heatIndexActiveTab || heatIndexTabs[0]) === tab 
                                                ? 'text-blue-600' 
                                                : 'text-gray-400 hover:text-gray-600'
                                        ]"
                                    >
                                        {{ tab }}
                                        <div v-if="(heatIndexActiveTab || heatIndexTabs[0]) === tab" class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600"></div>
                                    </button>
                                </div>

                                <!-- Top Pagination Controls -->
                                <div v-if="heatIndexTotalPages > 1" class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ heatIndexTotalPages }}</span>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase tracking-widest">({{ filteredHeatIndexRecords.length }} records)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="currentPage--" 
                                            :disabled="currentPage === 1"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="currentPage++" 
                                            :disabled="currentPage === heatIndexTotalPages"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Heat Index Matrix View for Summary -->
                                <div v-if="(heatIndexActiveTab || heatIndexTabs[0]) === 'Summary' && heatIndexSummaryMatrix" class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="bg-gray-100 border-b border-gray-200">
                                            <tr>
                                                <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase border-r border-gray-200">Date</th>
                                                <th v-for="station in heatIndexSummaryMatrix.stations" :key="station" class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center border-r border-gray-200">
                                                    {{ station }} (°C)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(row, idx) in heatIndexSummaryMatrix.rows" :key="idx" class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-2 text-sm text-gray-500 font-bold border-r border-gray-200">{{ row.dateLabel }}</td>
                                                <td v-for="station in heatIndexSummaryMatrix.stations" :key="station" class="px-4 py-2 text-sm text-gray-800 font-bold text-center border-r border-gray-200">
                                                    {{ row[station] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                            <tr class="font-bold">
                                                <td class="px-4 py-3 text-[10px] text-gray-600 uppercase border-r border-gray-200">Ave. Heat Index/day</td>
                                                <td v-for="station in heatIndexSummaryMatrix.stations" :key="station" class="px-4 py-3 text-sm text-green-700 text-center border-r border-gray-200">
                                                    {{ heatIndexSummaryMatrix.averages[station] }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <table v-else class="w-full text-left">
                                    <thead class="bg-gray-100 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase">No.</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase">Station Name</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Temp (°C)</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Humidity (%)</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Heat Index (°C)</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(record, index) in paginatedHeatIndexRecords" :key="index" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-500 font-bold font-mono">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold uppercase tracking-wider">{{ record.station_name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold text-center">{{ record.temperature }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold text-center">{{ record.humidity }}%</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold text-center">
                                                <span class="bg-orange-50 text-orange-700 px-2 py-1 rounded text-xs font-bold">
                                                    {{ record.heat_index }}°C
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 font-bold text-center">
                                                {{ new Date(record.date_time).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Pagination Controls -->
                                <div v-if="(heatIndexActiveTab || heatIndexTabs[0]) !== 'Summary' && heatIndexTotalPages > 1" class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ heatIndexTotalPages }}</span>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase tracking-widest">({{ filteredHeatIndexRecords.length }} records)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="currentPage--" 
                                            :disabled="currentPage === 1"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="currentPage++" 
                                            :disabled="currentPage === heatIndexTotalPages"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Water Level Chart -->
                        <div v-show="selectedReport === 'Water Level' && waterLevelRecords.length > 0" class="space-y-6">
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-bold text-gray-600 uppercase text-sm hidden">Water Level Sensor Data Chart</h4>
                                    <button 
                                        @click="downloadChart" 
                                        class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Download Chart
                                    </button>
                                </div>
                                <div class="bg-white p-2 rounded-sm border border-gray-100 h-[600px] w-full" ref="waterLevelChartDiv"></div>
                                <div class="text-center text-xs font-bold text-gray-800 uppercase">Timestamp</div>
                            </div>

                            <!-- Results Table -->
                            <div v-if="waterLevelRecords.length > 0" class="bg-white border border-gray-200 rounded-sm overflow-hidden">
                                <div class="flex justify-between items-center py-2 px-4">
                                    <h4 class="font-bold text-gray-600 uppercase text-sm">Water Level Data Table</h4>
                                    <div class="flex gap-2">
                                        <button 
                                            v-if="(waterLevelActiveTab || waterLevelTabs[0]) === 'Summary'"
                                            @click="exportSummaryToExcel" 
                                            class="bg-green-700 hover:bg-green-600 text-white px-4 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Export Summary
                                        </button>
                                        <button 
                                            v-else
                                            @click="exportToExcel" 
                                            class="bg-green-700 hover:bg-green-600 text-white px-4 py-1.5 rounded-sm font-bold uppercase tracking-wider text-[10px] transition-colors flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Export Data
                                        </button>
                                    </div>
                                </div>

                                <!-- Water Level Tab Navigation -->
                                <div v-if="waterLevelReport.sensor === 'All' && waterLevelTabs.length > 1" class="flex flex-wrap gap-2 border-b border-gray-100 px-4 pt-2 bg-gray-50/30">
                                    <button 
                                        v-for="tab in waterLevelTabs" 
                                        :key="tab"
                                        @click="waterLevelActiveTab = tab; currentPage = 1"
                                        :class="[
                                            'px-6 py-2.5 text-[10px] font-bold uppercase tracking-widest transition-all relative',
                                            (waterLevelActiveTab || waterLevelTabs[0]) === tab 
                                                ? 'text-blue-600' 
                                                : 'text-gray-400 hover:text-gray-600'
                                        ]"
                                    >
                                        {{ tab }}
                                        <div v-if="(waterLevelActiveTab || waterLevelTabs[0]) === tab" class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600"></div>
                                    </button>
                                </div>

                                <!-- Top Pagination Controls -->
                                <div v-if="waterLevelTotalPages > 1" class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ waterLevelTotalPages }}</span>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase tracking-widest">({{ filteredWaterLevelRecords.length }} records)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="currentPage--" 
                                            :disabled="currentPage === 1"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="currentPage++" 
                                            :disabled="currentPage === waterLevelTotalPages"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Water Level Matrix View for Summary -->
                                <div v-if="(waterLevelActiveTab || waterLevelTabs[0]) === 'Summary' && waterLevelSummaryMatrix" class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="bg-gray-100 border-b border-gray-200">
                                            <tr>
                                                <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase border-r border-gray-200">Date</th>
                                                <th v-for="sensor in waterLevelSummaryMatrix.sensors" :key="sensor" class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center border-r border-gray-200">
                                                    {{ sensor }} (m)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(row, idx) in waterLevelSummaryMatrix.rows" :key="idx" class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-2 text-sm text-gray-500 font-bold border-r border-gray-200">{{ row.dateLabel }}</td>
                                                <td v-for="sensor in waterLevelSummaryMatrix.sensors" :key="sensor" class="px-4 py-2 text-sm text-gray-800 font-bold text-center border-r border-gray-200">
                                                    {{ row[sensor] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                            <tr class="font-bold">
                                                <td class="px-4 py-3 text-[10px] text-gray-600 uppercase border-r border-gray-200">Ave. Water Level/day</td>
                                                <td v-for="sensor in waterLevelSummaryMatrix.sensors" :key="sensor" class="px-4 py-3 text-sm text-green-700 text-center border-r border-gray-200">
                                                    {{ waterLevelSummaryMatrix.averages[sensor] }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <table v-else class="w-full text-left">
                                    <thead class="bg-gray-100 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase">No.</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase">Sensor Name</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Water Level (m)</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase text-center">Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(record, index) in paginatedWaterLevelRecords" :key="index" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-500 font-bold font-mono">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold uppercase tracking-wider">{{ record.sensor_name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-800 font-bold text-center">
                                                <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs">
                                                    {{ record.water_level }}m
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 font-bold text-center">
                                                {{ new Date(record.date_time).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Pagination Controls -->
                                <div v-if="(waterLevelActiveTab || waterLevelTabs[0]) !== 'Summary' && waterLevelTotalPages > 1" class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ waterLevelTotalPages }}</span>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase tracking-widest">({{ filteredWaterLevelRecords.length }} records)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="currentPage--" 
                                            :disabled="currentPage === 1"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="currentPage++" 
                                            :disabled="currentPage === waterLevelTotalPages"
                                            class="p-1 rounded-sm bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Report Placeholder -->
                    <div v-else class="py-20 flex flex-col items-center justify-center space-y-4 border-2 border-dashed border-gray-100 rounded-sm bg-gray-50/50 mt-8">
                        <div class="relative">
                            <div v-if="hasSearched && rainRecords.length === 0 && heatIndexRecords.length === 0 && waterLevelRecords.length === 0" class="absolute -inset-1 bg-gradient-to-r from-red-600 to-red-400 rounded-full blur opacity-25 animate-pulse"></div>
                            <div v-else class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-blue-400 rounded-full blur opacity-25 animate-pulse"></div>
                            <div v-if="hasSearched && rainRecords.length === 0 && heatIndexRecords.length === 0 && waterLevelRecords.length === 0" class="relative bg-white p-4 rounded-full shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div v-else class="relative bg-white p-4 rounded-full shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            
                        </div>
                        <div class="text-center space-y-1">
                            <h3 class="text-lg font-bold text-gray-700 uppercase tracking-wider">{{ hasSearched && rainRecords.length === 0 && heatIndexRecords.length === 0 && waterLevelRecords.length === 0 ? 'No Records found' : 'No Report Generated yet' }}</h3>
                            <p class="text-sm text-gray-400 font-medium">{{ hasSearched && rainRecords.length === 0 && heatIndexRecords.length === 0 && waterLevelRecords.length === 0 ? 'Try adjusting your filters or date range.' : 'Please select your parameters above and click the "Generate" button.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Hide amCharts logo */
a[href*="amcharts"] {
    display: none !important;
}
</style>
