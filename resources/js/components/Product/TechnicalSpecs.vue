<template>
    <div class="specs-header">
        <h2>Thông số kỹ thuật</h2>
        <a href="#" class="view-more">Xem tất cả ></a>
    </div>
    <div class="table-wrapper">
        <table class="specs-table">
            <tbody>
                <tr v-for="spec in product?.specs" :key="spec.id">
                    <td class="label">{{ spec.name }}</td>
                    <td class="value" v-html="spec.value"></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>

const props = defineProps({
  product: {
    type: Object,
    default: null
  }
})
</script>

<style scoped>
.specs-box {
    border: 1px solid #dcdce0;
    border-radius: 12px;
    padding: 16px;
    background: #fff;
    width: 100%;
    margin-left: 5px;
}

.specs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    margin-top: 20px;
    margin-left: 10px;
}

.specs-header h2 {
    font-size: 20px;
    font-weight: 700;
}

.view-more {
    font-size: 14px;
    color: #2674ff;
    text-decoration: none;
}

.table-wrapper {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #ded2d2;
    margin-left: 10px;
}

.specs-table {
    width: 100%;
    border-collapse: collapse;
}

.specs-table tr {
    border: 1px solid #e2e2e2;
}

.specs-table td {
    padding: 10px;
    vertical-align: top;
}

.label {
    width: 35%;
    font-weight: 600;
    background: #f7f7f9;
    border-right: 1px solid #eee;
}

.value {
    width: 65%;
    color: #333;
}
</style>
