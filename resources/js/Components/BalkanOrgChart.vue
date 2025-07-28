<script setup>
import { onMounted, ref } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';

const props = defineProps({
  nodes: {
    type: Array,
    required: true,
  }
});

const chartContainer = ref(null);

onMounted(() => {
  if (chartContainer.value && props.nodes.length) {

    const chart = new OrgChart(chartContainer.value, {
      nodes: props.nodes,
      
      // --- THIS IS THE FIX ---
      // Disable the remote service to prevent the network error.
      // All calculations will now happen locally in the browser.
      enableSearch: false, // Disabling search also removes a remote call if not configured
      remote: {
            url: null
      },
      // --- END OF FIX ---

      // We will use a simple template as our base for CSS styling.
      template: "isla",

      // Enable interactive features.
      mouseScrool: OrgChart.action.zoom,
      
      // Map data fields to the node elements.
      nodeBinding: {
        field_0: "name",
        field_1: "title",
        img_0: "image"
      },

      // Add a CSS class for each node's level.
      levelSeparation: 70,

      // Apply a custom CSS class to the logged-in user's node for styling.
      tags: {
        "is-logged-in-user": {
          "node-class": "highlighted-user"
        }
      }
    });
  }
});
</script>

<template>
  <div ref="chartContainer" class="w-full h-[80vh] chart-container"></div>
</template>

<style>
/* 
  All detailed styling is handled in the global app.css file.
  This block just contains minor overrides for the library's built-in templates.
*/

/* Customizing the 'isla' template text alignment and color */
.boc-isla .boc-field-0 {
    font-size: 16px;
    font-weight: 600;
    fill: #1f2937; /* Dark Gray */
}
.boc-isla .boc-field-1 {
    font-size: 14px;
    fill: #6b7280; /* Medium Gray */
}
</style>