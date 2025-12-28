<template>
    <div class="title">
        <h3>Cam kết sản phẩm</h3>
    </div>
  <div class="commit-container">
    <div class="commit-box">
      <div class="icon">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <p>
        Máy mới 100% , chính hãng Apple Việt Nam. <br />
        CellphoneS hiện là đại lý bán lẻ uỷ quyền iPhone chính hãng VN/A của Apple Việt Nam
      </p>
    </div>

    <div class="commit-box">
      <div class="icon">
        <i class="fa-solid fa-rotate-left"></i>
      </div>
      <p>
        1 ĐỔI 1 trong 30 ngày nếu có lỗi phần cứng nhà sản xuất.  
        Bảo hành 12 tháng tại trung tâm bảo hành chính hãng Apple: CareS.vn
        <a href="#">Xem chi tiết</a> <br />
        Xem thông tin kích hoạt bảo hành các sản phẩm Apple 
        <a href="#">Chi tiết tại đây</a>
      </p>
    </div>

    <div class="commit-box">
      <div class="icon">
        <i class="fa-solid fa-box"></i>
      </div>
      <p>
        iPhone 16 Pro Max sử dụng iOS 18, Cáp Sạc USB-C (1m), Tài liệu
      </p>
    </div>

    <div class="commit-box">
      <div class="icon">
        <i class="fa-solid fa-file-invoice-dollar"></i>
      </div>
      <p>
        Giá sản phẩm <strong>đã bao gồm thuế VAT</strong>, có hỗ trợ
        <span class="highlight">hoàn thuế VAT - Tax Refund</span>
        cho khách du lịch.
      </p>
    </div>
  </div>
</template>

<script>
export default {
  name: "ProductCommit",
};
</script>

<style scoped>
.commit-container {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-top: 20px;
  margin-left: 10px;
}

.commit-box {
  background: #f7f7f8;
  height: 170px;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  gap: 12px;
}

.commit-box p {
  margin: 0;
  color: #444;
  font-size: 14px;
  line-height: 1.5;
}

.commit-box a {
  color: #0066ff;
  text-decoration: none;
  font-weight: 500;
}

.icon {
  font-size: 22px;
  color: #d70000;
  margin-top: 2px;
}

.highlight {
  color: #0066ff;
  font-weight: 600;
}

@media (max-width: 768px) {
  .commit-container {
    grid-template-columns: 1fr;
  }
}
.title h3 {
    font-size: 20px;
    font-weight: 700;
    margin-top: 30px;
    margin-left: 10px;
}
</style>
