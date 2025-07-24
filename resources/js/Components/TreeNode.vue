<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  node: Object
})


const isOpen = ref(true)

const hasChildren = computed(() => props.node?.children?.length > 0)
</script>

<template>
  <div class="org-tree-node">
    <div class="node-block-wrapper">
      <div class="node-block" @click="hasChildren && (isOpen = !isOpen)" :class="{ clickable: hasChildren }">
        <span v-if="hasChildren" class="toggle-icon">{{ isOpen ? '▼' : '►' }}</span>
        <img
          :src="node.image ? `/storage/${node.image}` : '/storage/defaults/default-avatar.jpg'"
          alt="Profile"
          class="node-image"
        />
        <span class="node-title">{{ node.name }}</span>
      </div>
    </div>

    <div v-if="isOpen && hasChildren" class="children-container">
      <div class="parent-connector"></div>
      <div class="children">
        <TreeNode
          v-for="child in node.children"
          :key="child.id"
          :node="child"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.org-tree-node {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  padding: 0 1rem;
}

.node-block-wrapper {
  position: relative;
  display: inline-block;
}

.node-block {
  display: flex;
  align-items: center;
  background: #e0e7ff;
  border: 1.6px solid #6366f1;
  border-radius: 8px;
  box-shadow: 0 2px 12px #6366f13c;
  padding: 8px 20px;
  font-weight: 600;
  color: #2e1065;
  text-align: center;
  cursor: default;
  position: relative;
  min-width: 150px;
  justify-content: center;
}
.node-block.clickable {
  cursor: pointer;
  transition: box-shadow 0.2s;
}
.node-block.clickable:hover {
  box-shadow: 0 4px 16px #818cf87c;
}
.toggle-icon {
  margin-right: 8px;
  color: #6366f1;
}
.node-title {
  color: #2d3a8c;
}

.children-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  margin-top: 20px;
}


.parent-connector {
  width: 2px;
  height: 20px;
  background-color: #6366f1;
  position: absolute;
  top: -20px;
  left: 50%;
  transform: translateX(-50%);
}

.children {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  gap: 2rem;
  padding-top: 20px;
  position: relative;
}
.children::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 2rem);
  height: 2px;
  background-color: #6366f1;
}

.children .org-tree-node:only-child ~ ::before {
    display: none;
}

.children > .org-tree-node::before {
  content: '';
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  width: 2px;
  height: 20px;
  background-color: #6366f1;
}

.node-image {
  width: 32px;
  height: 32px;
  border-radius: 9999px;
  object-fit: cover;
  margin-right: 8px;
  display: inline-block;
  vertical-align: middle;

  /* --- ADD THESE LINES FOR CLARITY --- */

  /* Helps ensure the browser's scaling algorithm is optimized for photos. */
  image-rendering: -webkit-optimize-contrast; /* For older Chrome/Safari */
  image-rendering: crisp-edges;
  image-rendering: smooth; /* A good default for photos */

  /* Fallback for very old browsers */
  -ms-interpolation-mode: nearest-neighbor;
}
</style>