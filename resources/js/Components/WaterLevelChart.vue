<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

const props = defineProps<{
    sensorId: number;
    name: string;
    value: number | undefined;
    timestamp: string;
    history?: Array<{
        value: number;
        timestamp: string;
    }>;
    level2?: number;
    level3?: number;
    level4?: number;
}>();

const chartDiv = ref<HTMLElement | null>(null);
let root: am5.Root | null = null;
let series: am5xy.LineSeries | null = null;
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
            layout: root.verticalLayout
        })
    );

    // Add legend
    const legend = chart.children.unshift(am5.Legend.new(root, {
        centerX: am5.p50,
        x: am5.p50,
        paddingBottom: 20
    }));

    // Define colors and ranges
    const l2 = Number(props.level2 ?? 500);
    const l3 = Number(props.level3 ?? 700);
    const l4 = Number(props.level4 ?? 1000);

    const ranges = [
        { label: `0-${l2.toFixed(2)}`, color: am5.color(0x10b981), from: 0, to: l2 },
        { label: `${(l2 + 0.01).toFixed(2)}-${l3.toFixed(2)}`, color: am5.color(0xf59e0b), from: l2, to: l3 },
        { label: `${(l3 + 0.01).toFixed(2)}-${l4.toFixed(2)}`, color: am5.color(0xf97316), from: l3, to: l4 },
        { label: `${(l4 + 0.01).toFixed(2)}+`, color: am5.color(0xef4444), from: l4, to: 10000 }
    ];

    legend.data.setAll(ranges.map(r => ({
        name: r.label,
        color: r.color,
        stroke: r.color
    })));

    legend.markers.template.setAll({
        width: 15,
        height: 15
    });

    // Add cursor
    const cursor = chart.set("cursor", am5xy.XYCursor.new(root, {
        behavior: "none"
    }));
    cursor.lineY.set("visible", false);

    // Create axes
    const xRenderer = am5xy.AxisRendererX.new(root, {
        minorGridEnabled: true,
        minGridDistance: 70
    });

    xAxis = chart.xAxes.push(
        am5xy.DateAxis.new(root, {
            maxDeviation: 0.2,
            baseInterval: { timeUnit: "second", count: 1 },
            renderer: xRenderer,
            tooltip: am5.Tooltip.new(root, {})
        })
    );

    xAxis.children.moveValue(am5.Label.new(root, {
        text: "Time",
        fontWeight: "500",
        textAlign: "center",
        x: am5.p50,
        centerX: am5.p50,
        paddingTop: 10,
        fontSize: 12,
        fill: am5.color(0x6b7280)
    }), xAxis.children.length);

    const yRenderer = am5xy.AxisRendererY.new(root, {
        minGridDistance: 30
    });

    yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, {
            renderer: yRenderer,
            min: 0,
            // max: 1500
        })
    );

    yAxis.children.moveValue(am5.Label.new(root, {
        rotation: -90,
        text: "Water Level (cm)",
        fontWeight: "500",
        y: am5.p50,
        centerX: am5.p50,
        paddingRight: 15,
        fontSize: 12,
        fill: am5.color(0x6b7280)
    }), 0);

    // Add horizontal dashed lines for alert levels
    const createRangeLine = (value: number, color: am5.Color, label: string) => {
        const rangeDataItem = yAxis!.makeDataItem({
            value: value,
            endValue: value
        });

        const range = yAxis!.createAxisRange(rangeDataItem);

        range.get("grid")?.setAll({
            stroke: color,
            strokeOpacity: 1,
            strokeWidth: 2,
            strokeDasharray: [5, 5],
            visible: true
        });
    };

    createRangeLine(l2, am5.color(0xf59e0b), "L2"); // Yellow
    createRangeLine(l3, am5.color(0xf97316), "L3"); // Orange
    createRangeLine(l4, am5.color(0xef4444), "L4"); // Red

    // Add background color regions
    const createBgRange = (from: number, to: number, color: am5.Color) => {
        const rangeDataItem = yAxis!.makeDataItem({
            value: from,
            endValue: to
        });

        const range = yAxis!.createAxisRange(rangeDataItem);

        range.get("axisFill")?.setAll({
            fill: color,
            fillOpacity: 0.05, // Subtle background
            visible: true
        });
    };

    createBgRange(0, l2, am5.color(0x10b981)); // Green
    createBgRange(l2, l3, am5.color(0xf59e0b)); // Yellow
    createBgRange(l3, l4, am5.color(0xf97316)); // Orange
    createBgRange(l4, 10000, am5.color(0xef4444)); // Red

    // Add series
    series = chart.series.push(
        am5xy.LineSeries.new(root, {
            name: props.name,
            xAxis: xAxis,
            yAxis: yAxis,
            valueYField: "value",
            valueXField: "date",
            stroke: am5.color(0x0284c7), // Sky blue line
            tooltip: am5.Tooltip.new(root, {
                labelText: "{valueY} cm\n{dateX.formatDate('HH:mm:ss')}",
                getFillFromSprite: false,
                autoTextColor: false
            })
        })
    );

    series.get("tooltip")!.get("background")!.setAll({
        fill: am5.color(0xffffff),
        stroke: am5.color(0x000000),
        strokeOpacity: 1,
        fillOpacity: 0.9
    });

    series.get("tooltip")!.label.setAll({
        fill: am5.color(0x000000),
        fontSize: 12,
        fontWeight: "600",
        textAlign: "center"
    });

    // Add bullets
    series.bullets.push(function (root: am5.Root, series: am5.Series, dataItem: am5.DataItem<am5xy.ILineSeriesDataItem>) {
        const value = (dataItem.get("valueY") as number) || 0;
        let color = am5.color(0x10b981); // Green

        if (value > l4) color = am5.color(0xef4444);
        else if (value > l3) color = am5.color(0xf97316);
        else if (value > l2) color = am5.color(0xf59e0b);

        const circle = am5.Circle.new(root, {
            radius: 4,
            fill: color,
            stroke: root.interfaceColors.get("background"),
            strokeWidth: 2
        });

        return am5.Bullet.new(root, {
            sprite: circle
        });
    });

    // Set line appearance
    series.strokes.template.setAll({
        strokeWidth: 2
    });

    // Handle initial/history data
    if (props.history && props.history.length > 0) {
        const historyData = props.history.map((point: { value: number; timestamp: string }) => ({
            date: new Date(point.timestamp).getTime(),
            value: Number(point.value)
        }));
        series.data.setAll(historyData);
    } else if (props.value !== undefined && props.value !== null) {
        const time = new Date(props.timestamp).getTime();
        series.data.push({
            date: time,
            value: Number(props.value)
        });
    }

    // Make stuff animate on load
    series.appear(1000);
    chart.appear(1000, 100);

    // Initial tooltip and axis scale
    series.events.on("datavalidated", () => {
        showLastTooltip();
        updateYAxisMax();
    });
});

const showLastTooltip = () => {
    if (!series || series.dataItems.length === 0) return;
    const lastDataItem = series.dataItems[series.dataItems.length - 1];
    if (lastDataItem) {
        series.showDataItemTooltip(lastDataItem);
    }
};

onUnmounted(() => {
    if (root) {
        root.dispose();
    }
});

const addDataPoint = (value: number, timestamp: string) => {
    if (!series) return;
    
    const time = new Date(timestamp).getTime();
    
    // Check if this point already exists to avoid duplicates
    const lastPoint: any = series.data.length > 0 ? series.data.getIndex(series.data.length - 1) : null;
    if (lastPoint && lastPoint.date === time) {
        return;
    }

    series.data.push({
        date: time,
        value: Number(value)
    });

    // Keep only last 60 points
    if (series.data.length > 61) {
        series.data.removeIndex(0);
    }

    // Always show tooltip for the latest point
    showLastTooltip();
};

const updateYAxisMax = () => {
    if (!yAxis) return;
    const l3 = Number(props.level3 || 0);
    const val = Number(props.value || 0);
    const newMax = l3 + val;
    
    // Set a reasonable minimum max if both are very small or zero
    yAxis.set("max", newMax > 10 ? newMax : 100);
};

watch(() => props.value, (newValue: number | undefined) => {
    if (newValue !== undefined && newValue !== null) {
        addDataPoint(Number(newValue), props.timestamp);
        updateYAxisMax();
    }
});

watch(() => props.level3, () => {
    updateYAxisMax();
});
</script>

<template>
    <div class="water-level-chart-container w-full">
        <div ref="chartDiv" class="w-full h-64"></div>
    </div>
</template>

<style scoped>
.water-level-chart-container {
    background: transparent;
}
</style>
