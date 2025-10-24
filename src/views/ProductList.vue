<template>
  <div class="container my-5">
    
    <h2 class="text-center mb-4">เมนูสินค้า</h2>

    <div class="mb-4 text-center">
      <label class="me-2 fw-bold">เลือกโต๊ะ:</label>
      <select v-model="selectedTable" class="form-select d-inline-block w-auto">
        <option disabled value="">-- เลือกโต๊ะ --</option>
        <option v-for="table in tables" :key="table" :value="table">
          โต๊ะ {{ table }}
        </option>
      </select>
      
      <div class="d-flex justify-content-center mt-3">
        <button 
          v-for="cat in categories" 
          :key="cat.value"
          :class="['btn', 'mx-1', 'btn-sm', {'btn-success': selectedCategory === cat.value, 'btn-outline-secondary': selectedCategory !== cat.value}]"
          @click="selectedCategory = cat.value"
        >
          {{ cat.text }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center">กำลังโหลดสินค้า...</div>
    <div v-else-if="error" class="alert alert-danger">❌ เกิดข้อผิดพลาดในการโหลดสินค้า: {{ error }}</div>
    <div v-else class="row">
      <div class="col-md-3" v-for="product in filteredProducts" :key="product.product_id">
        <div class="card shadow-sm mb-4">
          <img
            :src="'http://localhost:8081/MK_SHOP/php_api/uploads/' + product.image"
            class="card-img-top"
            height="200"
            :alt="product.product_name"
          />
          <div class="card-body text-center">
            <h5 class="card-title">{{ product.product_name }}</h5>
            <p class="card-text">{{ product.price }} บาท</p>
            <button class="btn btn-success" @click="addToCart(product)">
              สั่งเลย
            </button>
          </div>
        </div>
      </div>
      <div v-if="filteredProducts.length === 0" class="col-12 text-center text-muted">ไม่พบสินค้าในหมวดหมู่นี้</div>
    </div>

    <div class="mt-5">
      <h4>🧺 ตะกร้าสินค้า (โต๊ะ {{ selectedTable || '-' }})</h4>
      <table class="table table-bordered align-middle" v-if="cart.length > 0">
        <thead class="table-light">
          <tr>
            <th>สินค้า</th>
            <th>ราคา</th>
            <th style="width:150px;">จำนวน</th>
            <th>รวม</th>
            <th>ลบ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in cart" :key="index">
            <td>{{ item.product_name }}</td>
            <td>{{ item.price }}</td>
            <td class="text-center">
              <div class="d-flex justify-content-center align-items-center">
                <button
                  class="btn btn-sm btn-outline-secondary me-2"
                  @click="decreaseQty(item)"
                >
                  -
                </button>
                <span>{{ item.quantity }}</span>
                <button
                  class="btn btn-sm btn-outline-secondary ms-2"
                  @click="increaseQty(item)"
                >
                  +
                </button>
              </div>
            </td>
            <td>{{ (item.price * item.quantity).toFixed(2) }}</td>
            <td>
              <button
                class="btn btn-danger btn-sm"
                @click="removeFromCart(index)"
              >
                ลบ
              </button>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3" class="text-end fw-bold">รวมทั้งหมด</td>
            <td colspan="2" class="fw-bold">{{ totalPrice.toFixed(2) }} บาท</td>
          </tr>
        </tfoot>
      </table>

      <div v-else class="text-muted">ยังไม่มีสินค้าในตะกร้า</div>

      <div class="text-end mt-3" v-if="cart.length > 0">
        <button class="btn btn-primary" @click="submitOrder">
          ยืนยันการสั่งซื้อ
        </button>
      </div>
    </div>
  </div>
</template>
<script>
import { ref, computed, onMounted } from "vue";

export default {
  name: "ProductList",
  setup() {
    const products = ref([]); // เก็บข้อมูลสินค้าทั้งหมด
    const cart = ref([]);
    const selectedTable = ref("");
    const tables = [1, 2, 3, 4, 5];
    const loading = ref(true);
    const error = ref(null);

    // 🆕 ส่วนที่เพิ่ม: จัดการหมวดหมู่
    const categories = ref([
        { text: 'ทั้งหมด', value: 'all' },
        { text: 'เครื่องดื่ม', value: 'เครื่องดื่ม' },
        { text: 'อาหาร', value: 'เนื้อสัตว์' },
        { text: 'พิเศษ', value: 'ผัก' }, // เพิ่มหมวดหมู่พิเศษ (ตามรูป)
    ]);
    const selectedCategory = ref('all'); // เริ่มต้นที่ 'ทั้งหมด'

    // ✅ ดึงข้อมูลสินค้า (ยังคงเดิม แต่ต้องแน่ใจว่าสินค้ามี 'category' field)
    const fetchProducts = async () => {
      try {
        const response = await fetch(
          "http://localhost:8081/MK_SHOP/php_api/show_product.php"
        );
        const result = await response.json();
        if (result.success) {
          // ⚠️ ข้อสังเกต: ในโค้ดจริง คุณอาจต้องเพิ่ม field 'category' 
          // ให้กับสินค้าแต่ละตัวจากการเรียก API
products.value = result.data.map(p => ({
  ...p,
  // ใช้ category_name หรือ category_id จาก API ได้เลย
  category: p.category_name === 'เครื่องดื่ม' ? 'เครื่องดื่ม' : 'เนื้อสัตว์' 
}));
        } else {
          error.value = result.message;
        }
      } catch (err) {
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };
    
    // 🆕 ส่วนที่เพิ่ม: กรองสินค้าตามหมวดหมู่ที่เลือก
    const filteredProducts = computed(() => {
        if (selectedCategory.value === 'all') {
            return products.value;
        }
        // ในรูปมีสินค้า 'แดงโซดา' 'ชาไทย' 'ชาเขียว' 'น้ำเปล่า' เป็นเครื่องดื่ม (drink)
        // และ 'หมูกระทะ' 'เนื้อ' 'ชุดรวม' เป็นอาหาร (food)
        return products.value.filter(
            (product) => product.category === selectedCategory.value
        );
    });

    // ... ฟังก์ชันอื่น ๆ ยังคงเดิม ...

    // ✅ เพิ่มสินค้าเข้าตะกร้า (คงเดิม)
    const addToCart = (product) => {
        if (!selectedTable.value) {
            alert("⚠️ กรุณาเลือกโต๊ะก่อนสั่งสินค้า");
            return;
        }
        const existing = cart.value.find(
            (item) => item.product_id === product.product_id
        );

        if (existing) {
            existing.quantity++;
        } else {
            cart.value.push({
                product_id: product.product_id,
                product_name: product.product_name,
                price: parseFloat(product.price),
                quantity: 1,
            });
        }
        alert(`✅ เพิ่ม "${product.product_name}" ลงในตะกร้าสำเร็จ!`);
    };

    // ✅ เพิ่มจำนวนสินค้า (คงเดิม)
    const increaseQty = (item) => {
      item.quantity++;
    };

    // ✅ ลดจำนวนสินค้า (คงเดิม)
    const decreaseQty = (item) => {
      if (item.quantity > 1) {
        item.quantity--;
      } else {
        if (confirm("ต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?")) {
          const index = cart.value.indexOf(item);
          if (index !== -1) cart.value.splice(index, 1);
        }
      }
    };

    // ✅ ลบสินค้าออกจากตะกร้า (คงเดิม)
    const removeFromCart = (index) => {
      if (confirm("ยืนยันการลบสินค้านี้หรือไม่?")) {
        cart.value.splice(index, 1);
      }
    };

    // ✅ คำนวณราคารวมทั้งหมด (คงเดิม)
    const totalPrice = computed(() =>
      cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
    );

    // ✅ ยืนยันการสั่งซื้อ (คงเดิม)
    const submitOrder = async () => {
        if (!selectedTable.value) {
            alert("⚠️ กรุณาเลือกโต๊ะก่อนสั่งสินค้า");
            return;
        }

        if (cart.value.length === 0) {
            alert("⚠️ กรุณาเพิ่มสินค้าในตะกร้าก่อนสั่งซื้อ");
            return;
        }

        const orderData = {
            table_no: selectedTable.value,
            items: cart.value.map((item) => ({
                product_id: item.product_id,
                product_name: item.product_name,
                quantity: item.quantity,
                price: item.price,
            })),
            total: totalPrice.value,
        };

        try {
            const response = await fetch(
                "http://localhost:8081/MK_SHOP/php_api/order.php",
                {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(orderData),
                }
            );

            const result = await response.json();

            if (result.success) {
                alert("✅ สั่งซื้อสำเร็จ!");
                cart.value = []; // ล้างตะกร้า
            } else {
                alert("❌ " + result.message);
            }
        } catch (error) {
            alert("เกิดข้อผิดพลาด: " + error.message);
        }
    };


    onMounted(fetchProducts);

    return {
      products,
      cart,
      selectedTable,
      tables,
      totalPrice,
      addToCart,
      increaseQty,
      decreaseQty,
      removeFromCart,
      submitOrder,
      loading,
      error,
      // 🆕 ส่วนที่เพิ่ม:
      categories,
      selectedCategory,
      filteredProducts, // ส่ง filteredProducts ไปใช้ใน template
    };
  },
};
</script>