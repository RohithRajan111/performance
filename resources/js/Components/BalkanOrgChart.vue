<script setup>
import { onMounted, ref, watch } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';

// ✅ 1. ADD A NEW 'layout' PROP
// This allows the parent component to choose the layout.
// We'll default to 'vertical' to solve your immediate problem.
const props = defineProps({
  nodes: {
    type: Array,
    required: true,
  },
  layout: {
    type: String,
    default: 'vertical', // 'vertical' or 'horizontal'
  }
});

const chartContainer = ref(null);
// We need a ref to hold the chart instance so we can update it later.
const chartInstance = ref(null);

// ✅ 2. CREATE A HELPER FUNCTION
// This function translates our simple prop ('vertical') into the library's specific constant.
const getLayoutConstant = (layoutName) => {
  if (layoutName === 'vertical') {
    // This layout arranges nodes top-to-bottom.
    return OrgChart.layout.tree;
  }
  // This is the default horizontal layout.
  return OrgChart.layout.normal;
};


onMounted(() => {
  if (chartContainer.value && props.nodes.length) {

    // Initialize the chart with the correct layout from the start.
    chartInstance.value = new OrgChart(chartContainer.value, {
      nodes: props.nodes,

      // ✅ 3. USE THE NEW LAYOUT OPTION
      // Set the initial layout based on the prop.
      layout: getLayoutConstant(props.layout),

      // --- Other options (unchanged) ---
      enableSearch: false,
      remote: {
            url: null
      },
      template: "isla",
      mouseScrool: OrgChart.action.zoom,
      nodeBinding: {
        field_0: "name",
        field_1: "title",
        img_0: "image"
      },
      levelSeparation: 70,
      tags: {
        "is-logged-in-user": {
          "node-class": "highlighted-user"
        }
      }
    });
  }
});

// ✅ 4. WATCH FOR CHANGES TO THE LAYOUT PROP
// If the parent component changes the layout (e.g., via a toggle),
// this will update the chart without needing to remount everything.
watch(() => props.layout, (newLayout) => {
  if (chartInstance.value) {
    // Use the library's API to update the layout configuration
    chartInstance.value.config.set('layout', getLayoutConstant(newLayout));
    // Redraw the chart to apply the new layout
    chartInstance.value.draw();
  }
});

</script>

<template>
  <div ref="chartContainer" class="w-full h-[80vh] chart-container"></div>
</template>

<style>
/* --- Styles (unchanged) --- */

.boc-isla .boc-field-0 {
    font-size: 16px;
    font-weight: 600;
    fill: #1f2937;
}
.boc-isla .boc-field-1 {
    font-size: 14px;
    fill: #6b7280;
}
</style>
