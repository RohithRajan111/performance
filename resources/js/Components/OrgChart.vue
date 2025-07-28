<script setup>
import { onMounted, ref, watch, nextTick, onUnmounted, computed } from 'vue';

const props = defineProps({
  nodes: {
    type: Array,
    required: true,
  },
  config: {
    type: Object,
    default: () => ({})
  }
});

const chartContainer = ref(null);
const isLoading = ref(true);
const error = ref(null);
let chartInstance = null;
let OrgChart = null;

// Computed config with performance optimizations
const chartConfig = computed(() => ({
  nodes: props.nodes,
  template: "rony",
  mouseScrool: true, // Fixed typo: should be mouseScroll
  enableDragDrop: true,
  scaleInitial: 0.8, // Start with a smaller scale for better overview
  searchDisplayField: "name",
  nodeBinding: {
    field_0: "name",
    field_1: "title",
    img_0: "image"
  },
  tags: {
    "ceo-style": {
      template: "olivia"
    },
    "manager-style": {
      nodeBGColor: "#039BE5",
    },
    "tech-style": {
      nodeBGColor: "#4CAF50",
    },
    "hr-team": {
      nodeBGColor: "#673AB7",
    }
  },
  // Performance optimizations
  lazyLoading: true,
  enableSearch: true,
  toolbar: {
    fit: true,
    expandAll: true,
    fullScreen: true,
    zoom: true
  },
  ...props.config
}));

// Debounced resize handler for better performance
let resizeTimeout = null;
const handleResize = () => {
  if (resizeTimeout) clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(() => {
    if (chartInstance && chartInstance.fit) {
      chartInstance.fit();
    }
  }, 150);
};

// Initialize chart with error handling
const initChart = async () => {
  try {
    isLoading.value = true;
    error.value = null;

    // Lazy load the OrgChart library
    if (!OrgChart) {
      const module = await import('@balkangraph/orgchart.js');
      OrgChart = module.default;
    }

    if (!chartContainer.value || !props.nodes.length) {
      return;
    }

    // Define custom templates with better styling
    if (OrgChart.templates && OrgChart.templates.rony) {
      OrgChart.templates.rony.field_1 = '<text class="field_1" style="font-size: 14px;" fill="#6b7280" x="125" y="100" text-anchor="middle">{val}</text>';
      OrgChart.templates.rony.field_0 = '<text class="field_0" style="font-size: 16px; font-weight: 600;" fill="#1f2937" x="125" y="80" text-anchor="middle">{val}</text>';
    }

    // Destroy existing chart instance
    if (chartInstance) {
      chartInstance.destroy();
    }

    // Wait for next tick to ensure DOM is ready
    await nextTick();

    // Create new chart instance
    chartInstance = new OrgChart(chartContainer.value, chartConfig.value);

    // Add event listeners for better UX
    chartInstance.on('click', (sender, args) => {
      console.log('Node clicked:', args);
    });

    chartInstance.on('ready', () => {
      isLoading.value = false;
    });

    // Add resize listener
    window.addEventListener('resize', handleResize);

  } catch (err) {
    console.error('Error initializing org chart:', err);
    error.value = 'Failed to load organization chart';
    isLoading.value = false;
  }
};

// Watch for nodes changes and reinitialize
watch(() => props.nodes, (newNodes) => {
  if (newNodes && newNodes.length > 0) {
    initChart();
  }
}, { deep: true });

// Initialize on mount
onMounted(() => {
  initChart();
});

// Cleanup on unmount
onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
  if (resizeTimeout) {
    clearTimeout(resizeTimeout);
  }
  window.removeEventListener('resize', handleResize);
});
</script>

<template>
  <div class="relative w-full h-full min-h-[600px]">
    <!-- Loading state -->
    <div 
      v-if="isLoading" 
      class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 z-10"
    >
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-sm text-gray-600">Loading organization chart...</p>
      </div>
    </div>

    <!-- Error state -->
    <div 
      v-if="error" 
      class="absolute inset-0 flex items-center justify-center bg-red-50 z-10"
    >
      <div class="text-center p-6">
        <svg class="mx-auto h-16 w-16 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-red-800">Chart Load Error</h3>
        <p class="mt-2 text-sm text-red-600">{{ error }}</p>
        <button 
          @click="initChart" 
          class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- Chart container with enhanced styling -->
    <div 
      ref="chartContainer" 
      class="w-full h-full rounded-lg border border-gray-200 shadow-sm overflow-hidden"
      :class="{ 'opacity-50': isLoading }"
    ></div>

    <!-- Chart controls -->
    <div 
      v-if="!isLoading && !error" 
      class="absolute top-4 right-4 flex space-x-2 z-20"
    >
      <button 
        @click="chartInstance?.fit()" 
        class="inline-flex items-center p-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        title="Fit to screen"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
        </svg>
      </button>
      <button 
        @click="chartInstance?.expandAll()" 
        class="inline-flex items-center p-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        title="Expand all"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l3-3m0 0l3 3m-3-3v6m0-6V4" />
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
/* Enhanced chart styling */
:deep(.boc-light) {
  background: transparent !important;
}

:deep(.boc-isla .boc-node-rect) {
  fill: #ffffff;
  stroke: #e5e7eb;
  stroke-width: 1px;
  rx: 12px;
  transition: all 0.2s ease;
}

:deep(.boc-node:hover .boc-node-rect) {
  stroke: #4f46e5;
  stroke-width: 2px;
  filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1));
}

:deep(.boc-node.highlighted-user .boc-node-rect) {
  fill: rgba(79, 70, 229, 0.05);
  stroke: #4f46e5;
  stroke-width: 2px;
  backdrop-filter: blur(4px);
}

:deep(.boc-isla .boc-img-0) {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 3px solid white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

:deep(.boc-link) {
  stroke: #d1d5db;
  stroke-width: 1.5px;
  stroke-dasharray: 4;
}

:deep(.boc-exp-coll) {
  stroke: #9ca3af;
  stroke-width: 2px;
  fill: #f9fafb;
  transition: all 0.2s ease;
}

:deep(.boc-exp-coll:hover) {
  stroke: #4f46e5;
  transform: scale(1.1);
}
</style>