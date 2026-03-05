<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

const props = defineProps<{
    heights: Array<{
        dt: number;
        date: string;
        height: number;
    }>;
    extremes?: Array<{
        dt: number;
        date: string;
        height: number;
        type: string;
    }>;
}>();

const chartDiv = ref<HTMLElement | null>(null);
let root: am5.Root | null = null;
let series: am5xy.LineSeries | null = null;
let xAxis: am5xy.DateAxis<am5xy.AxisRenderer> | null = null;

onMounted(() => {
    if (!chartDiv.value) return;

    root = am5.Root.new(chartDiv.value);
    root.setThemes([am5themes_Animated.new(root)]);

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

    // Create axes
    const xRenderer = am5xy.AxisRendererX.new(root, {
        minorGridEnabled: true,
        minGridDistance: 70,
        strokeOpacity: 0.1
    });

    xAxis = chart.xAxes.push(
        am5xy.DateAxis.new(root, {
            maxDeviation: 0.2,
            baseInterval: { timeUnit: "minute", count: 30 },
            renderer: xRenderer,
            tooltip: am5.Tooltip.new(root, {})
        })
    );

    const yRenderer = am5xy.AxisRendererY.new(root, {
        minGridDistance: 30,
        strokeOpacity: 0.1
    });

    const yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, {
            renderer: yRenderer,
        })
    );

    // Add series
    series = chart.series.push(
        am5xy.LineSeries.new(root, {
            name: "Tide Level",
            xAxis: xAxis,
            yAxis: yAxis,
            valueYField: "height",
            valueXField: "dt",
            stroke: am5.color(0x3b82f6), // Blue-500
            fill: am5.color(0x3b82f6),
            tooltip: am5.Tooltip.new(root, {
                labelText: "{valueY}m\n{valueX.formatDate('MMM d, HH:mm')}"
            })
        })
    );

    series.fills.template.setAll({
        fillOpacity: 0.2,
        visible: true
    });

    series.strokes.template.setAll({
        strokeWidth: 3
    });

    // Add extremes as bullets if provided
    if (props.extremes) {
        series.bullets.push((root, series, dataItem) => {
            const dt = dataItem.get("valueX") as number;
            const extreme = props.extremes?.find(e => e.dt * 1000 === dt);
            
            if (extreme) {
                const container = am5.Container.new(root, {});
                
                container.children.push(am5.Circle.new(root, {
                    radius: 5,
                    fill: extreme.type === 'High' ? am5.color(0x2563eb) : am5.color(0x0d9488),
                    stroke: root.interfaceColors.get("background"),
                    strokeWidth: 2
                }));

                container.children.push(am5.Label.new(root, {
                    text: `${extreme.type === 'High' ? '↑' : '↓'} ${extreme.height.toFixed(2)}m`,
                    fontSize: 10,
                    fontWeight: "bold",
                    centerX: am5.p50,
                    centerY: extreme.type === 'High' ? am5.p100 : am5.p0,
                    dy: extreme.type === 'High' ? -10 : 10,
                    fill: extreme.type === 'High' ? am5.color(0x2563eb) : am5.color(0x0d9488)
                }));

                return am5.Bullet.new(root, {
                    sprite: container
                });
            }
            return undefined;
        });
    }

    const data = props.heights.map(h => ({
        dt: h.dt * 1000,
        height: h.height
    }));

    series.data.setAll(data);
    series.appear(1000);
    chart.appear(1000, 100);
});

onUnmounted(() => {
    if (root) {
        root.dispose();
    }
});

watch(() => props.heights, (newHeights) => {
    if (series) {
        const data = newHeights.map(h => ({
            dt: h.dt * 1000,
            height: h.height
        }));
        series.data.setAll(data);
    }
}, { deep: true });
</script>

<template>
    <div class="tide-chart-container w-full bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div ref="chartDiv" class="w-full h-80"></div>
    </div>
</template>

<style scoped>
.tide-chart-container {
    min-height: 320px;
}
</style>
