<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

const props = defineProps<{
    sensors: Array<{
        id: number;
        name: string;
    }>;
    latestData?: Record<number, {
        data: number;
        timestamp: string;
        success: boolean;
    }>;
    historyData?: Record<number, Array<{
        value: number;
        timestamp: string;
    }>>;
}>();

const chartDiv = ref<HTMLElement | null>(null);
let root: am5.Root | null = null;
let chart: am5xy.XYChart | null = null;
let xAxis: am5xy.DateAxis<am5xy.AxisRenderer> | null = null;
let yAxis: am5xy.ValueAxis<am5xy.AxisRenderer> | null = null;
const seriesMap = new Map<number, am5xy.LineSeries>();

const colors = [
    0x0284c7, // Sky Blue
    0x10b981, // Emerald
    0xf59e0b, // Amber
    0xef4444, // Red
    0x8b5cf6, // Violet
    0xec4899, // Pink
];

onMounted(() => {
    if (!chartDiv.value) return;

    root = am5.Root.new(chartDiv.value);
    root.setThemes([am5themes_Animated.new(root)]);

    chart = root.container.children.push(
        am5xy.XYChart.new(root, {
            panX: true,
            panY: false,
            wheelX: "panX",
            wheelY: "zoomX",
            pinchZoomX: true,
            layout: root.verticalLayout
        })
    );

    xAxis = chart.xAxes.push(
        am5xy.DateAxis.new(root, {
            maxDeviation: 0.2,
            baseInterval: { timeUnit: "second", count: 1 },
            renderer: am5xy.AxisRendererX.new(root, {
                minorGridEnabled: true,
                minGridDistance: 70
            }),
            tooltip: am5.Tooltip.new(root, {})
        })
    );

    yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, {
            renderer: am5xy.AxisRendererY.new(root, {
                minGridDistance: 30
            }),
            min: 0,
            max: 2000,
        })
    );

    const legend = chart.children.push(am5.Legend.new(root, {
        centerX: am5.p50,
        x: am5.p50,
        paddingTop: 15,
    }));

    // Create series for each sensor
    props.sensors.forEach((sensor, index: number) => {
        const series = chart!.series.push(
            am5xy.LineSeries.new(root!, {
                name: sensor.name,
                xAxis: xAxis!,
                yAxis: yAxis!,
                valueYField: "value",
                valueXField: "date",
                stroke: am5.color(colors[index % colors.length]),
                tooltip: am5.Tooltip.new(root!, {
                    labelText: "{name}: {valueY} cm",
                    getFillFromSprite: false,
                    autoTextColor: false
                })
            })
        );

        series.get("tooltip")!.get("background")!.setAll({
            fill: am5.color(0xffffff),
            stroke: am5.color(colors[index % colors.length]),
            strokeOpacity: 0.8,
            fillOpacity: 0.9
        });

        series.get("tooltip")!.label.setAll({
            fill: am5.color(0x000000),
            fontSize: 11,
            fontWeight: "600"
        });

        series.strokes.template.setAll({
            strokeWidth: 2
        });

        seriesMap.set(sensor.id, series);

        // Load initial history
        if (props.historyData?.[sensor.id]) {
            const data = props.historyData[sensor.id].map((p: { value: number; timestamp: string }) => ({
                date: new Date(p.timestamp).getTime(),
                value: Number(p.value)
            }));
            series.data.setAll(data);
        }
    });

    legend.data.setAll(chart.series.values);

    chart.set("cursor", am5xy.XYCursor.new(root, {
        behavior: "none"
    }));

    chart.appear(1000, 100);
});

onUnmounted(() => {
    if (root) root.dispose();
});

// Watch for latest data updates
watch(() => props.latestData, (newData) => {
    if (!newData) return;

    Object.entries(newData).forEach(([sensorIdStr, result]: [string, any]) => {
        const sensorId = Number(sensorIdStr);
        const series = seriesMap.get(sensorId);
        
        if (series && result.success) {
            const time = new Date(result.timestamp).getTime();
            
            // Avoid duplicate timestamps
            const lastPoint: any = series.data.length > 0 ? series.data.getIndex(series.data.length - 1) : null;
            if (lastPoint && lastPoint.date === time) return;

            series.data.push({
                date: time,
                value: Number(result.data)
            });

            if (series.data.length > 100) {
                series.data.removeIndex(0);
            }
        }
    });
}, { deep: true });
</script>

<template>
    <div class="bg-white dark:bg-gray-800 border-2 border-blue-600 rounded-2xl p-6 shadow-md mb-8">
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
            </svg>
            All Sensors Trend
        </h3>
        <div ref="chartDiv" class="w-full h-80"></div>
    </div>
</template>
