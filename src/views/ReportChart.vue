<template>
  <div class="container my-5">
    <h2 class="text-center mb-4">📊 รายงานยอดขาย</h2>

    <div class="text-center mb-4">
      <select
        v-model="selectedType"
        class="form-select w-auto d-inline-block"
        @change="fetchData"
      >
        <option value="table">ยอดขายตามโต๊ะ</option>
        <option value="daily">ยอดขายรายวัน</option>
        <option value="status">จำนวนออเดอร์ตามสถานะ</option>
      </select>
    </div>

    <div v-if="loading" class="text-center">⏳ กำลังโหลดข้อมูล...</div>
    <div v-if="error" class="text-danger text-center">{{ error }}</div>

    <!-- แสดงกราฟ -->
    <canvas v-show="!loading && !error" ref="reportChart" height="120"></canvas>
  </div>
</template>

<script>
import { ref, onMounted, nextTick } from "vue";
import { Chart, registerables } from "chart.js";
Chart.register(...registerables);

export default {
  name: "ReportChart",
  setup() {
    const loading = ref(true);
    const error = ref(null);
    const selectedType = ref("table");
    const reportChart = ref(null);
    let chartInstance = null;

    // 🎨 กำหนดสีเฉพาะสำหรับสถานะต่างๆ
    const statusColors = {
      "รอดำเนินการ": "rgba(255, 206, 86, 0.6)", // เหลือง
      "กำลังทำ": "rgba(54, 162, 235, 0.6)", // น้ำเงิน
      "เสร็จแล้ว": "rgba(75, 192, 192, 0.6)", // เขียว
      "ยกเลิก": "rgba(255, 99, 132, 0.6)", // แดง
      "default": "rgba(153, 102, 255, 0.6)" // ม่วง (กรณีอื่นๆ)
    };

    const fetchData = async () => {
      loading.value = true;
      error.value = null;

      try {
        const res = await fetch(
          `http://localhost:8081/MK_SHOP/php_api/report_orders.php?type=${selectedType.value}`
        );
        const data = await res.json();

        if (!data.success) throw new Error(data.message);

        const labels = data.data.map((item) => item.label);
        const values = data.data.map((item) => parseFloat(item.total) || 0);

        await nextTick();

        if (chartInstance) chartInstance.destroy();

        const ctx = reportChart.value.getContext("2d");

        let chartLabel = "";
        let chartType = "bar";
        let bgColors = [];
        let borderColors = [];

        if (selectedType.value === "daily") {
          chartLabel = "ยอดขายรวมต่อวัน (บาท)";
          chartType = "line";
          bgColors = ["rgba(255, 159, 64, 0.6)"];
          borderColors = ["rgba(255, 159, 64, 1)"];
        } else if (selectedType.value === "status") {
          chartLabel = "จำนวนออเดอร์แยกตามสถานะ";
          chartType = "pie";

          // ใช้สีเฉพาะตามสถานะ
          bgColors = labels.map(
            (status) => statusColors[status] || statusColors["default"]
          );
          borderColors = bgColors.map((c) => c.replace("0.6", "1"));
        } else {
          chartLabel = "ยอดขายรวมต่อโต๊ะ (บาท)";
          bgColors = ["rgba(54, 162, 235, 0.6)"];
          borderColors = ["rgba(54, 162, 235, 1)"];
        }

        chartInstance = new Chart(ctx, {
          type: chartType,
          data: {
            labels,
            datasets: [
              {
                label: chartLabel,
                data: values,
                backgroundColor:
                  chartType === "pie" ? bgColors : bgColors[0],
                borderColor:
                  chartType === "pie" ? borderColors : borderColors[0],
                borderWidth: 1,
                fill: chartType === "line",
                tension: 0.3, // เส้นโค้งสำหรับ line chart
              },
            ],
          },
          options: {
            responsive: true,
            plugins: {
              legend: { position: "top" },
              title: { display: true, text: chartLabel },
            },
            scales:
              chartType !== "pie"
                ? {
                    y: {
                      beginAtZero: true,
                      title: {
                        display: true,
                        text:
                          selectedType.value === "status"
                            ? "จำนวนออเดอร์"
                            : "ยอดขาย (บาท)",
                      },
                    },
                  }
                : {},
          },
        });
      } catch (err) {
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };

    onMounted(fetchData);

    return { loading, error, selectedType, fetchData, reportChart };
  },
};
</script>

<style scoped>
select.form-select {
  min-width: 250px;
}
</style>
