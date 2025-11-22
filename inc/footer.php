</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Tìm nút bấm
    const toggleButton = document.getElementById("sidebarToggleBtn");

    // Kiểm tra xem nút có tồn tại không (để không lỗi trên màn hình mobile đã ẩn nút)
    if (toggleButton) {
        // Lắng nghe sự kiện click
        toggleButton.addEventListener("click", function() {
            // Thêm/xóa class 'sidebar-toggled' trên <body>
            document.body.classList.toggle("sidebar-toggled");

            // (Tùy chọn) Lưu trạng thái vào localStorage để nhớ lựa chọn của người dùng
            if (document.body.classList.contains("sidebar-toggled")) {
                localStorage.setItem("sidebarToggled", "true");
            } else {
                localStorage.removeItem("sidebarToggled");
            }
        });

        // (Tùy chọn) Kiểm tra xem người dùng đã thu gọn từ lần trước chưa
        // Nếu có, tự động thu gọn khi tải trang
        if (localStorage.getItem("sidebarToggled") === "true") {
            document.body.classList.add("sidebar-toggled");
        }
    }
});
</script>
</body>

</html>