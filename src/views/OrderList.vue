<template>
  <div class="container my-5">
    <h2 class="text-center mb-4">💰 สรุปยอดคำสั่งซื้อ (เสร็จแล้ว)</h2>

    <!-- 🔍 ตัวกรองค้นหาตามโต๊ะหรือรหัสคำสั่งซื้อ -->
    <div class="mb-3 d-flex align-items-center gap-2">
      <select v-model="searchBy" class="form-select w-auto">
        <option value="table_no">ค้นหาตามโต๊ะ</option>
        <option value="order_id">ค้นหาตามรหัสคำสั่งซื้อ</option>
      </select>
      <input
        type="text"
        class="form-control"
        :placeholder="searchBy === 'table_no' ? '🔍 กรอกหมายเลขโต๊ะ...' : '🔍 กรอกรหัสคำสั่งซื้อ...'"
        v-model="searchText"
      />
    </div>

    <div v-if="loading" class="text-center">⏳ กำลังโหลดข้อมูล...</div>
    <div v-if="error" class="text-danger text-center">{{ error }}</div>

    <!-- ✅ ตารางสรุป -->
    <table v-if="paginatedOrders.length" class="table table-bordered table-striped mt-3">
      <thead class="table-primary text-center">
        <tr>
          <th>รหัสคำสั่งซื้อ</th>
          <th>โต๊ะ</th>
          <th>จำนวนรายการ</th>
          <th>ยอดรวมทั้งหมด</th>
          <th>วันที่สั่ง</th>
          <th>สถานะ</th>
          <th>รายละเอียด</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(order, index) in paginatedOrders" :key="index">
          <td>{{ order.order_id }}</td>
          <td>{{ order.table_no }}</td>
          <td>{{ order.items_count }}</td>
          <td>{{ order.total_amount.toFixed(2) }}</td>
          <td>{{ order.order_date }}</td>
          <td>
            <span :class="['fw-bold', getStatusClass(order.status)]">{{ order.status }}</span>
          </td>
          <td>
            <button class="btn btn-sm btn-primary" @click="showDetails(order)">ดูรายละเอียด</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-else-if="!loading" class="text-center text-muted">
      ❗ ยังไม่มีข้อมูลคำสั่งซื้อ
    </div>

    <!-- 📄 Pagination -->
    <div v-if="orderSummaries.length > 0" class="d-flex justify-content-between align-items-center mt-4">
      <div>
        แสดง
        <select v-model.number="rowsPerPage" class="form-select d-inline-block w-auto mx-2">
          <option v-for="n in [5,10,20,50]" :key="n" :value="n">{{ n }}</option>
        </select>
        แถวต่อหน้า
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" @click="prevPage" :disabled="currentPage === 1">◀ ก่อนหน้า</button>
        <span>หน้า {{ currentPage }} / {{ totalPages }}</span>
        <button class="btn btn-outline-primary btn-sm" @click="nextPage" :disabled="currentPage === totalPages">ถัดไป ▶</button>
      </div>
    </div>

    <!-- 🧾 Modal แสดงรายละเอียด -->
    <div v-if="selectedOrder" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              รายละเอียดคำสั่งซื้อ #{{ selectedOrder.order_id }} (โต๊ะ {{ selectedOrder.table_no }}) 
              - วันที่สั่ง: {{ selectedOrder.order_date }}
            </h5>
            <button type="button" class="btn-close" @click="selectedOrder=null"></button>
          </div>
          <div class="modal-body">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>รหัสสินค้า</th>
                  <th>สินค้า</th>
                  <th>จำนวน</th>
                  <th>ราคา/หน่วย</th>
                  <th>ราคารวม</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedOrder.items" :key="item.product_id">
                  <td>{{ item.product_id }}</td>
                  <td>{{ item.product_name }}</td>
                  <td>{{ item.quantity }}</td>
                  <td>{{ Number(item.price).toFixed(2) }}</td>
                  <td>{{ Number(item.subtotal).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
            <h5 class="text-end">ยอดรวมทั้งหมด: {{ selectedOrder.total_amount.toFixed(2) }}</h5>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from "vue";

export default {
  name: "OrderSummary",
  setup() {
    const orders = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const searchText = ref("");
    const searchBy = ref("table_no");
    const selectedOrder = ref(null);

    const currentPage = ref(1);
    const rowsPerPage = ref(10);

    const fetchOrders = async () => {
      loading.value = true;
      error.value = null;
      try {
        const res = await fetch("http://localhost:8081/MK_SHOP/php_api/order_bill.php");
        const data = await res.json();
        if (data.success) {
          orders.value = data.data.map(o => ({
            ...o,
            subtotal: Number(o.subtotal),
            price: Number(o.price)
          }));
        } else {
          error.value = data.message || "ไม่สามารถโหลดข้อมูลได้";
        }
      } catch (err) {
        error.value = "เกิดข้อผิดพลาด: " + err.message;
      } finally {
        loading.value = false;
      }
    };

    onMounted(fetchOrders);

    // 🔎 กรองตามตัวเลือกค้นหา
    const filteredOrders = computed(() => {
      if (!searchText.value) return orders.value;
      const kw = searchText.value.toString().toLowerCase();
      return orders.value.filter(o => {
        if (searchBy.value === "table_no") return o.table_no.toString().includes(kw);
        if (searchBy.value === "order_id") return o.order_id.toString().includes(kw);
        return true;
      });
    });

    // 📊 สรุปคำสั่งซื้อเป็น order + รายการสินค้า
    const orderSummaries = computed(() => {
      const summary = {};
      filteredOrders.value.forEach(o => {
        const key = `${o.order_id}-${o.table_no}`;
        if (!summary[key]) {
          summary[key] = {
            order_id: o.order_id,
            table_no: o.table_no,
            items: [],
            total_amount: 0,
            items_count: 0,
            status: o.status,
            order_date: o.order_date // เพิ่มวันที่
          };
        }
        summary[key].items.push(o);
        summary[key].total_amount += o.subtotal;
        summary[key].items_count += 1;
      });
      return Object.values(summary);
    });

    // 📄 Pagination
    const totalPages = computed(() => Math.ceil(orderSummaries.value.length / rowsPerPage.value));
    const paginatedOrders = computed(() => {
      const start = (currentPage.value - 1) * rowsPerPage.value;
      return orderSummaries.value.slice(start, start + rowsPerPage.value);
    });

    const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
    const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
    watch([rowsPerPage, searchText, searchBy], () => { currentPage.value = 1; });

    // 🎨 สีสถานะ
    const getStatusClass = (status) => {
      status = status.toLowerCase();
      if (status.includes("เสร็จ")) return "text-success";
      if (status.includes("รอดำเนินการ")) return "text-warning";
      if (status.includes("ยกเลิก")) return "text-danger";
      return "text-secondary";
    };

    // 🧾 ดูรายละเอียด
    const showDetails = (order) => {
      selectedOrder.value = order;
    };

    return {
      orders,
      loading,
      error,
      searchText,
      searchBy,
      selectedOrder,
      paginatedOrders,
      orderSummaries,
      currentPage,
      rowsPerPage,
      totalPages,
      nextPage,
      prevPage,
      getStatusClass,
      showDetails
    };
  }
};
</script>

<style scoped>
select.form-select { min-width: 80px; }
.table th, .table td { vertical-align: middle; }
.modal { z-index: 1050; }
</style>
