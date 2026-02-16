<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

const props = defineProps<{
    stationId: number;
    name: string;
    history?: Array<{
        data: any;
        timestamp: string;
    }>;
}>();

const chartDiv = ref<HTMLElement | null>(null);
let root: am5.Root | null = null;
let totalSeries: am5xy.LineSeries | null = null;
let rateSeries: am5xy.LineSeries | null = null;
let xAxis: am5xy.DateAxis<am5xy.AxisRenderer> | null = null;
let yAxis: am5xy.ValueAxis<am5xy.AxisRenderer> | null = null;

onMounted(() => {
    if (!chartDiv.value) return;

    // Create root element
    root = am5.Root.new(chartDiv.value);

    // Set themes
    root.setThemes([
        am5themes_Animated.new(root)
    ]);

    // Create chart
    const chart = root.container.children.push(
        am5xy.XYChart.new(root, {
            panX: true,
            panY: false,
            wheelX: "panX",
            wheelY: "zoomX",
            pinchZoomX: true,
            paddingLeft: 0,
            paddingRight: 10,
            paddingBottom: 0,
            paddingTop: 10,
            layout: root.verticalLayout
        })
    );

    // Add cursor
    const cursor = chart.set("cursor", am5xy.XYCursor.new(root, {
        behavior: "none"
    }));
    cursor.lineY.set("visible", false);

    // Create axes
    const xRenderer = am5xy.AxisRendererX.new(root, {
        minorGridEnabled: true,
        minGridDistance: 70,
        strokeOpacity: 0.1
    });

    xAxis = chart.xAxes.push(
        am5xy.DateAxis.new(root, {
            maxDeviation: 0.2,
            baseInterval: { timeUnit: "minute", count: 1 },
            renderer: xRenderer,
            tooltip: am5.Tooltip.new(root, {})
        })
    );

    const yRenderer = am5xy.AxisRendererY.new(root, {
        minGridDistance: 30,
        strokeOpacity: 0.1
    });

    yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, {
            renderer: yRenderer,
            min: 0,
            strictMinMax: false
        })
    );

    // Add background stripes
    const createStripe = (from: number, to: number, color: am5.Color) => {
        const rangeDataItem = yAxis!.makeDataItem({
            value: from,
            endValue: to
        });

        const range = yAxis!.createAxisRange(rangeDataItem);

        range.get("axisFill")?.setAll({
            fill: color,
            fillOpacity: 0.05,
            visible: true
        });
    };

    for (let i = 0; i < 20; i += 5) {
        if (i % 10 === 0) {
            createStripe(i, i + 5, am5.color(0x000000)); // Subtle dark stripe
        }
    }

    // Add series 1: Precip. Accum. Total
    totalSeries = chart.series.push(
        am5xy.LineSeries.new(root, {
            name: "Rain Total (mm)",
            xAxis: xAxis,
            yAxis: yAxis,
            valueYField: "total",
            valueXField: "date",
            stroke: am5.color(0x0284c7), // Sky blue
            fill: am5.color(0x0284c7),
            tooltip: am5.Tooltip.new(root, {
                labelText: "Total: {valueY} mm",
                getFillFromSprite: false,
                autoTextColor: false
            })
        })
    );

    totalSeries.get("tooltip")!.get("background")!.setAll({
        fill: am5.color(0xffffff),
        stroke: am5.color(0x0284c7),
        strokeOpacity: 1,
        fillOpacity: 0.9
    });

    totalSeries.get("tooltip")!.label.setAll({
        fill: am5.color(0x000000),
        fontSize: 12
    });

    totalSeries.strokes.template.setAll({
        strokeWidth: 2
    });

    // Add series 2: Precip. Rate
    rateSeries = chart.series.push(
        am5xy.LineSeries.new(root, {
            name: "Rain Rate (mm)",
            xAxis: xAxis,
            yAxis: yAxis,
            valueYField: "rate",
            valueXField: "date",
            stroke: am5.color(0x65a30d), // Lime green
            fill: am5.color(0x65a30d),
            tooltip: am5.Tooltip.new(root, {
                labelText: "Rate: {valueY} mm/h",
                getFillFromSprite: false,
                autoTextColor: false
            })
        })
    );

    rateSeries.get("tooltip")!.get("background")!.setAll({
        fill: am5.color(0xffffff),
        stroke: am5.color(0x65a30d),
        strokeOpacity: 1,
        fillOpacity: 0.9
    });

    rateSeries.get("tooltip")!.label.setAll({
        fill: am5.color(0x000000),
        fontSize: 12
    });

    rateSeries.strokes.template.setAll({
        strokeWidth: 2
    });

    // Add legend
    const legend = chart.children.push(am5.Legend.new(root, {
        centerX: am5.p100,
        x: am5.p100,
        paddingTop: 10,
    }));

    legend.labels.template.setAll({
        fontSize: 10,
        fontWeight: "500",
        fill: am5.color(0x4b5563)
    });

    legend.data.setAll(chart.series.values);

    // Process data and set axis range
    if (props.history && props.history.length > 0) {
        const processedData = props.history.map(item => ({
            date: new Date(item.timestamp).getTime(),
            total: Number(item.data.precipitation_total),
            rate: Number(item.data.precipitation_rate)
        }));
        
        // Sort by date to ensure line renders correctly
        processedData.sort((a, b) => a.date - b.date);
        
        totalSeries.data.setAll(processedData);
        rateSeries.data.setAll(processedData);

        // Set axis range to 00:00 to 23:59 of the latest record's date
        const latestDate = new Date(processedData[processedData.length - 1].date);
        const startOfDay = new Date(latestDate);
        startOfDay.setHours(0, 0, 0, 0);
        const endOfDay = new Date(latestDate);
        endOfDay.setHours(23, 59, 59, 999);

        xAxis.set("min", startOfDay.getTime());
        xAxis.set("max", endOfDay.getTime());
    }

    // Make stuff animate on load
    totalSeries.appear(1000);
    rateSeries.appear(1000);
    chart.appear(1000, 100);
});

onUnmounted(() => {
    if (root) {
        root.dispose();
    }
});

watch(() => props.history, (newHistory) => {
    if (newHistory && totalSeries && rateSeries && xAxis) {
        const processedData = newHistory.map(item => ({
            date: new Date(item.timestamp).getTime(),
            total: Number(item.data.precipitation_total),
            rate: Number(item.data.precipitation_rate)
        }));
        
        processedData.sort((a, b) => a.date - b.date);
        
        totalSeries.data.setAll(processedData);
        rateSeries.data.setAll(processedData);

        if (processedData.length > 0) {
            const latestDate = new Date(processedData[processedData.length - 1].date);
            const startOfDay = new Date(latestDate);
            startOfDay.setHours(0, 0, 0, 0);
            const endOfDay = new Date(latestDate);
            endOfDay.setHours(23, 59, 59, 999);

            xAxis.set("min", startOfDay.getTime());
            xAxis.set("max", endOfDay.getTime());
        }
    }
}, { deep: true });
</script>

<template>
    <div class="weather-station-chart-container w-full h-full min-h-[250px]">
        <div ref="chartDiv" class="w-full h-full"></div>
    </div>
</template>

<style scoped>
.weather-station-chart-container {
    background: transparent;
}
</style>
