<template>
    <div class="my-container">
        <div class="left-box">
            <a href="#" class="a1">
                <span class="cps-image-cdn">
                    <img loading="lazy" width="214" height="530" decoding="async" data-nimg="1" src="https://cdn2.cellphones.com.vn/insecure/rs:fill:214:530/q:90/plain/https://media-asset.cellphones.com.vn/page_configs/01K6F050R8WY7PZK75J36ST4CV.png" 
                    alt="Banner iphone 17 pro home desktop" class="my-image" :style="{color: `transparent`}">
                </span>
            </a>
            <a href="#" class="a1">
                <span class="cps-image-cdn">
                    <img loading="lazy" width="214" height="530" decoding="async" data-nimg="1" src="https://cdn2.cellphones.com.vn/insecure/rs:fill:214:530/q:90/plain/https://media-asset.cellphones.com.vn/page_configs/01K6F050ZM5Q7TATVRHDGHEMAV.jpg" 
                    alt="Banner iphone 17 pro home desktop" class="my-image" :style="{color: `transparent`}">
                </span>
            </a>
        </div>
        <div class="right-box">
            <div class="topbar-box">
                <button class="phone">Điện thoại</button>
                <div class="middle"></div>
                <button class="ipad">Máy tính bảng</button>
            </div>
            <div class="bottom-box">

            </div>
        </div>
    </div>
</template>
<style scoped>
    .my-container{
         display: flex;
  /* flex-direction: column; */
  gap: 1rem;
  max-width: 1280px; /* max-w-screen-xl */
  margin: 0 auto; /* center the content */
  padding: 1rem;
  font-family: 'Inter', sans-serif;
    }
 .left-box {
  display: none;            /* hidden -> ẩn mặc định */
  flex: 1;                  /* flex-1 -> chiếm toàn bộ không gian khả dụng trong flex container */
  flex-direction: column;   /* flex-col -> sắp xếp theo cột */
  justify-content: space-between; /* justify-between -> dàn đều theo trục chính */
  gap: 0.75rem;             /* gap-3 -> khoảng cách 12px giữa các phần tử con */
}

/* empty:hidden -> nếu phần tử rỗng thì ẩn */
.left-box:empty {
  display: none;
}

/* md:flex -> hiển thị lại khi màn hình >= 768px */
@media (min-width: 768px) {
  .left-box {
    display: flex;
  }
}

/* CSS */
.a1 {
  display: block;             /* block */
  height: fit-content;        /* h-fit -> chiều cao vừa đủ nội dung */
  width: 100%;                /* w-full -> chiếm 100% chiều ngang */
  padding-top: 0;             /* py-0 -> padding trên = 0 */
  padding-bottom: 0;          /* py-0 -> padding dưới = 0 */
  line-height: 1;             /* leading-none -> line-height = 1 */
}
.cps-image-cdn {
  position: relative;          /* relative */
  display: inline-block;       /* inline-block */
  width: 100%;                 /* w-full */
  border-radius: 1rem;         /* rounded-2xl -> 1rem (16px) */
  object-fit: cover;           /* object-cover */
  transition: all 150ms ease;  /* transition-all duration-150 */
}
.my-image {
  opacity: 1;                  /* opacity-100 */
  border-radius: 1rem;         /* rounded-2xl = 1rem (16px) */
  object-fit: cover;           /* object-cover (ghi đè object-contain) */
  transition: all 150ms ease;  /* transition-all duration-150 (ghi đè transition-opacity duration-500) */
}
.right-box {
  width: 100%; /* w-full */
}

@media (min-width: 768px) {
  .right-box {
    width: 80%; /* w-4/5 */
  }
}
.topbar-box {
  display: flex;            /* flex */
  align-items: stretch;     /* items-stretch */
}
.phone {
  /* --- Cơ bản --- */
  flex: 1;                           /* flex-1: chiếm đều không gian trong flex container */
  cursor: pointer;                   /* cursor-pointer: trỏ chuột dạng tay */
  min-height: 3rem;                  /* min-h-12 = 3rem (48px) */
  padding-left: 0.5rem;              /* px-2 = 0.5rem (8px) */
  padding-right: 0.5rem;
  border: none;
  border-bottom: 1px solid red; /* border-b + border-primary-500 */
  color: red;   /* text-primary-500 */
  font-size: 1rem;                   /* text-base (16px) */
  font-weight: 600;                  /* font-semibold */
  line-height: 1.5;                  /* mặc định của text-base */
  transition: all 200ms ease-in-out; /* transition-all duration-200 ease-in-out */

  /* --- Nền gradient --- */
  background-image: linear-gradient(
    to top,
    rgba(240, 10, 10, 0.11) 5%,  /* from-primary-500/11 from-5% */
    rgba(255, 255, 255, 0.05) 60%                /* to-pure-white/5 to-60% */
  );

  /* Bo chữ cái đầu viết hoa */
  text-transform: none;               /* mặc định */
}

.phone::first-letter {
  text-transform: uppercase;          /* first-letter:uppercase */
}

/* --- Màn hình >= 768px (md) --- */
@media (min-width: 768px) {
  .phone {
    font-size: 1.125rem;              /* md:text-md (giả định text-md ~ 18px) */
    min-height: 3.75rem;              /* md:min-h-15 = 3.75rem (60px) */
    padding-left: 1rem;               /* md:px-4 = 1rem (16px) */
    padding-right: 1rem;
    text-transform: uppercase;        /* md:uppercase */
  }
}
.middle {
  margin-top: auto;              /* my-auto → margin-y: auto */
  margin-bottom: auto;
  height: 1.25rem;               /* h-5 = 1.25rem (20px) */
  border-left: 1px solid #e5e5e5;/* border-l + border-neutral-200 (#e5e5e5 là giá trị mặc định của Tailwind neutral-200) */
}
.ipad {
  /* --- Cơ bản --- */
  flex: 1;                           /* flex-1: chiếm đều không gian trong flex container */
  cursor: pointer;                   /* cursor-pointer: trỏ chuột dạng tay */
  min-height: 3rem;                  /* min-h-12 = 3rem (48px) */
  padding-left: 0.5rem;              /* px-2 = 0.5rem (8px) */
  padding-right: 0.5rem;
  border: none;
  color: black;   /* text-primary-500 */
  font-size: 1rem;                   /* text-base (16px) */
  font-weight: 600;                  /* font-semibold */
  line-height: 1.5;                  /* mặc định của text-base */
  transition: all 200ms ease-in-out; /* transition-all duration-200 ease-in-out */

  /* --- Nền gradient --- */
  background-image: linear-gradient(
    to top,
    rgba(240, 10, 10, 0.11) 5%,  /* from-primary-500/11 from-5% */
    rgba(255, 255, 255, 0.05) 60%                /* to-pure-white/5 to-60% */
  );

  /* Bo chữ cái đầu viết hoa */
  text-transform: none;               /* mặc định */
}

.ipad::first-letter {
  text-transform: uppercase;          /* first-letter:uppercase */
}

/* --- Màn hình >= 768px (md) --- */
@media (min-width: 768px) {
  .ipad {
    font-size: 1.125rem;              /* md:text-md (giả định text-md ~ 18px) */
    min-height: 3.75rem;              /* md:min-h-15 = 3.75rem (60px) */
    padding-left: 1rem;               /* md:px-4 = 1rem (16px) */
    padding-right: 1rem;
    text-transform: uppercase;        /* md:uppercase */
  }
}
.bottom-box {
  margin-top: 0.5rem; /* mt-2 = 0.5rem (8px) */
}

@media (min-width: 768px) {
  .bottom-box {
    margin-top: 1rem; /* md:mt-4 = 1rem (16px) khi màn hình ≥768px */
  }
}

</style>