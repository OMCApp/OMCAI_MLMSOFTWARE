# Post-deploy check — https://mlmsoftware.omc.co.th

Run 2026-11-05 16:35 (Asia/Bangkok) · mode: production · `php tools/launch/postcheck.php`

**FAIL — 13 problem(s):**
- #78 404 /mlm-system/
- #79 404 /mlmsoftware/
- #80 404 /contact-us/
- #81 404 /contact-us-2/
- #82 404 /homepage-business-solutions/
- #83 404 /pages/
- production: robots.txt must Allow and list https://mlmsoftware.omc.co.th/sitemap.xml
- production: Strict-Transport-Security header missing
- production: http://www.mlmsoftware.omc.co.th must 301 once to https://mlmsoftware.omc.co.th keeping path + query (got 301 https://www.mlmsoftware.omc.co.th/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/?utm_source=postcheck)
- production: http://www.mlmsoftware.omc.co.th target answers 301 (chain?)
- sitemap.xml 301
- /_health is not 200
- /config/config.php is reachable (302)

## 95 baseline URLs

| HTTP | URLs |
|---|---|
| 200 | 86 |
| 301→200 | 2 |
| 404 | 6 |
| 405 | 1 |

<details><summary>All URLs</summary>

| # | Old URL | HTTP | Canonical |
|---|---|---|---|
| 1 | `https://mlmsoftware.omc.co.th/` | 200 | `https://mlmsoftware.omc.co.th/` |
| 2 | `https://mlmsoftware.omc.co.th/2024/05/22/รายชื่อผู้ประกอบธุรกิจ/` | 200 | `https://mlmsoftware.omc.co.th/2024/05/22/รายชื่อผู้ประกอบธุรกิจ/` |
| 3 | `https://mlmsoftware.omc.co.th/2024/05/22/ขายตรง-และ-ตลาดแบบตรง/` | 200 | `https://mlmsoftware.omc.co.th/2024/05/22/ขายตรง-และ-ตลาดแบบตรง/` |
| 4 | `https://mlmsoftware.omc.co.th/2024/06/17/กิฟฟารีน-mlm-อันดับ-1-ของไทย/` | 200 | `https://mlmsoftware.omc.co.th/2024/06/17/กิฟฟารีน-mlm-อันดับ-1-ของไทย/` |
| 5 | `https://mlmsoftware.omc.co.th/2024/07/15/10-ข้อ-ที่คุณอ่านแล้วจะทำ/` | 200 | `https://mlmsoftware.omc.co.th/2024/07/15/10-ข้อ-ที่คุณอ่านแล้วจะทำ/` |
| 6 | `https://mlmsoftware.omc.co.th/2024/08/19/เพราะอะไร-ธุรกิจขายตรง/` | 200 | `https://mlmsoftware.omc.co.th/2024/08/19/เพราะอะไร-ธุรกิจขายตรง/` |
| 7 | `https://mlmsoftware.omc.co.th/2024/09/16/multi-level-marketing-หรือ-mlm/` | 200 | `https://mlmsoftware.omc.co.th/2024/09/16/multi-level-marketing-หรือ-mlm/` |
| 8 | `https://mlmsoftware.omc.co.th/2024/10/16/8-ข้อ-ทำไมโปรแกรมขายตรงจ/` | 200 | `https://mlmsoftware.omc.co.th/2024/10/16/8-ข้อ-ทำไมโปรแกรมขายตรงจ/` |
| 9 | `https://mlmsoftware.omc.co.th/2024/11/18/ช่วยให้ธุรกิจสามารถจัด/` | 200 | `https://mlmsoftware.omc.co.th/2024/11/18/ช่วยให้ธุรกิจสามารถจัด/` |
| 10 | `https://mlmsoftware.omc.co.th/2024/12/16/5-ฟีเจอร์-mlm-software-ปี-2025/` | 200 | `https://mlmsoftware.omc.co.th/2024/12/16/5-ฟีเจอร์-mlm-software-ปี-2025/` |
| 11 | `https://mlmsoftware.omc.co.th/2025/01/22/ทำไม-mlm-software-ถึงเหนือกว่า/` | 200 | `https://mlmsoftware.omc.co.th/2025/01/22/ทำไม-mlm-software-ถึงเหนือกว่า/` |
| 12 | `https://mlmsoftware.omc.co.th/2025/02/17/mlm-software-by-omc/` | 200 | `https://mlmsoftware.omc.co.th/2025/02/17/mlm-software-by-omc/` |
| 13 | `https://mlmsoftware.omc.co.th/2025/03/18/downline-และ-upline-ในธุรกิจ-mlm/` | 200 | `https://mlmsoftware.omc.co.th/2025/03/18/downline-และ-upline-ในธุรกิจ-mlm/` |
| 14 | `https://mlmsoftware.omc.co.th/2025/04/21/ธุรกิจเครือข่าย-mlm-top-5-ของไทย/` | 200 | `https://mlmsoftware.omc.co.th/2025/04/21/ธุรกิจเครือข่าย-mlm-top-5-ของไทย/` |
| 15 | `https://mlmsoftware.omc.co.th/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/` | 200 | `https://mlmsoftware.omc.co.th/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/` |
| 16 | `https://mlmsoftware.omc.co.th/2025/05/29/ประกันรถยนต์-เป็นสิ่งสำ/` | 200 | `https://mlmsoftware.omc.co.th/2025/05/29/ประกันรถยนต์-เป็นสิ่งสำ/` |
| 17 | `https://mlmsoftware.omc.co.th/2025/06/18/omc-broker-โบรกเกอร์ประกันรถยนต์/` | 200 | `https://mlmsoftware.omc.co.th/2025/06/18/omc-broker-โบรกเกอร์ประกันรถยนต์/` |
| 18 | `https://mlmsoftware.omc.co.th/2025/06/20/7-mindset-เพื่อสำเร็จในธุรกิจ-mlm/` | 200 | `https://mlmsoftware.omc.co.th/2025/06/20/7-mindset-เพื่อสำเร็จในธุรกิจ-mlm/` |
| 19 | `https://mlmsoftware.omc.co.th/2025/07/15/อยากเติบโตในธุรกิจขายต/` | 200 | `https://mlmsoftware.omc.co.th/2025/07/15/อยากเติบโตในธุรกิจขายต/` |
| 20 | `https://mlmsoftware.omc.co.th/2025/07/17/ประกันรถยนต์-แบบไหนคุ้ม/` | 200 | `https://mlmsoftware.omc.co.th/2025/07/17/ประกันรถยนต์-แบบไหนคุ้ม/` |
| 21 | `https://mlmsoftware.omc.co.th/2025/08/19/5-เคล็ดลับเพิ่มประสิทธิภ/` | 200 | `https://mlmsoftware.omc.co.th/2025/08/19/5-เคล็ดลับเพิ่มประสิทธิภ/` |
| 22 | `https://mlmsoftware.omc.co.th/2025/08/22/เคสประกันรถยนต์ปฏิเสธค/` | 200 | `https://mlmsoftware.omc.co.th/2025/08/22/เคสประกันรถยนต์ปฏิเสธค/` |
| 23 | `https://mlmsoftware.omc.co.th/2025/09/16/คู่มือสำหรับขายตรง-mlm/` | 200 | `https://mlmsoftware.omc.co.th/2025/09/16/คู่มือสำหรับขายตรง-mlm/` |
| 24 | `https://mlmsoftware.omc.co.th/2025/09/17/บริษัทประกันรถยนต์ที่ค/` | 200 | `https://mlmsoftware.omc.co.th/2025/09/17/บริษัทประกันรถยนต์ที่ค/` |
| 25 | `https://mlmsoftware.omc.co.th/2025/10/21/7-วิธี-สร้างทัศนคติ-mlm/` | 200 | `https://mlmsoftware.omc.co.th/2025/10/21/7-วิธี-สร้างทัศนคติ-mlm/` |
| 26 | `https://mlmsoftware.omc.co.th/2025/10/24/พ-ร-บ-ประกันภาคบังคับ/` | 200 | `https://mlmsoftware.omc.co.th/2025/10/24/พ-ร-บ-ประกันภาคบังคับ/` |
| 27 | `https://mlmsoftware.omc.co.th/2025/11/20/mlm-software-ปฏิวัติธุรกิจเครือข่/` | 200 | `https://mlmsoftware.omc.co.th/2025/11/20/mlm-software-ปฏิวัติธุรกิจเครือข่/` |
| 28 | `https://mlmsoftware.omc.co.th/2025/11/20/ทำประกันชีวิตและสุขภาพ/` | 200 | `https://mlmsoftware.omc.co.th/2025/11/20/ทำประกันชีวิตและสุขภาพ/` |
| 29 | `https://mlmsoftware.omc.co.th/2025/12/17/ประกันชีวิตใช้ลดหย่อนภ/` | 200 | `https://mlmsoftware.omc.co.th/2025/12/17/ประกันชีวิตใช้ลดหย่อนภ/` |
| 30 | `https://mlmsoftware.omc.co.th/2025/12/22/เรื่อเล่าตัวแทนขายตรง-mlm/` | 200 | `https://mlmsoftware.omc.co.th/2025/12/22/เรื่อเล่าตัวแทนขายตรง-mlm/` |
| 31 | `https://mlmsoftware.omc.co.th/2026/02/24/features-member-mlm-software/` | 200 | `https://mlmsoftware.omc.co.th/2026/02/24/features-member-mlm-software/` |
| 32 | `https://mlmsoftware.omc.co.th/2026/03/20/จัดการ-pv-bv-ด้วย-mlm-software/` | 200 | `https://mlmsoftware.omc.co.th/2026/03/20/จัดการ-pv-bv-ด้วย-mlm-software/` |
| 33 | `https://mlmsoftware.omc.co.th/2026/03/20/เลือก-ประกันชีวิต-ให้ถูก/` | 200 | `https://mlmsoftware.omc.co.th/2026/03/20/เลือก-ประกันชีวิต-ให้ถูก/` |
| 34 | `https://mlmsoftware.omc.co.th/2026/06/26/โปรแกรมขายตรง-mlm-software/` | 200 | `https://mlmsoftware.omc.co.th/2026/06/26/โปรแกรมขายตรง-mlm-software/` |
| 35 | `https://mlmsoftware.omc.co.th/2026/07/14/mlm-software-system/` | 200 | `https://mlmsoftware.omc.co.th/2026/07/14/mlm-software-system/` |
| 36 | `https://mlmsoftware.omc.co.th/2026/09/25/mlm-software-commission/` | 200 | `https://mlmsoftware.omc.co.th/2026/09/25/mlm-software-commission/` |
| 37 | `https://mlmsoftware.omc.co.th/2026/10/20/เลือกโปรแกรม-mlm/` | 200 | `https://mlmsoftware.omc.co.th/2026/10/20/เลือกโปรแกรม-mlm/` |
| 38 | `https://mlmsoftware.omc.co.th/sample-page/` | 200 | `https://mlmsoftware.omc.co.th/sample-page/` |
| 39 | `https://mlmsoftware.omc.co.th/home-startup/` | 200 | `https://mlmsoftware.omc.co.th/home-startup/` |
| 40 | `https://mlmsoftware.omc.co.th/services/` | 200 | `https://mlmsoftware.omc.co.th/services/` |
| 41 | `https://mlmsoftware.omc.co.th/about-us-3/` | 200 | `https://mlmsoftware.omc.co.th/about-us-3/` |
| 42 | `https://mlmsoftware.omc.co.th/mlm-software-omc/` | 200 | `https://mlmsoftware.omc.co.th/mlm-software-omc/` |
| 43 | `https://mlmsoftware.omc.co.th/ocpb-mlm2/` | 200 | `https://mlmsoftware.omc.co.th/ocpb-mlm2/` |
| 44 | `https://mlmsoftware.omc.co.th/portfolio/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/` |
| 45 | `https://mlmsoftware.omc.co.th/blog/` | 200 | `https://mlmsoftware.omc.co.th/blog/` |
| 46 | `https://mlmsoftware.omc.co.th/contact/` | 200 | `https://mlmsoftware.omc.co.th/contact/` |
| 47 | `https://mlmsoftware.omc.co.th/newsletter-2/` | 200 | `https://mlmsoftware.omc.co.th/newsletter-2/` |
| 48 | `https://mlmsoftware.omc.co.th/ocpb-mlm/` | 200 | `https://mlmsoftware.omc.co.th/ocpb-mlm/` |
| 49 | `https://mlmsoftware.omc.co.th/sample-page-2/` | 200 | `https://mlmsoftware.omc.co.th/sample-page-2/` |
| 50 | `https://mlmsoftware.omc.co.th/newsletter/` | 200 | `https://mlmsoftware.omc.co.th/newsletter/` |
| 51 | `https://mlmsoftware.omc.co.th/นโยบายความเป็นส่วนตัวข/` | 200 | `https://mlmsoftware.omc.co.th/นโยบายความเป็นส่วนตัวข/` |
| 52 | `https://mlmsoftware.omc.co.th/นโยบายการใช้คุกกี้-cookies-policy/` | 200 | `https://mlmsoftware.omc.co.th/นโยบายการใช้คุกกี้-cookies-policy/` |
| 53 | `https://mlmsoftware.omc.co.th/portfolio/allen-walker-optimization/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/allen-walker-optimization/` |
| 54 | `https://mlmsoftware.omc.co.th/portfolio/dell-audience-monitoring/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/dell-audience-monitoring/` |
| 55 | `https://mlmsoftware.omc.co.th/portfolio/hp-cloud-service-improvement/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/hp-cloud-service-improvement/` |
| 56 | `https://mlmsoftware.omc.co.th/portfolio/travel-tour-marketing-campaign/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/travel-tour-marketing-campaign/` |
| 57 | `https://mlmsoftware.omc.co.th/portfolio/real-estate-web-rank-improve/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/real-estate-web-rank-improve/` |
| 58 | `https://mlmsoftware.omc.co.th/portfolio/kleanity-social-engagement-2/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/kleanity-social-engagement-2/` |
| 59 | `https://mlmsoftware.omc.co.th/portfolio/website-seo-optimization-2/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/website-seo-optimization-2/` |
| 60 | `https://mlmsoftware.omc.co.th/portfolio/conference-marketing-2/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/conference-marketing-2/` |
| 61 | `https://mlmsoftware.omc.co.th/portfolio/architek-rank-improving/` | 200 | `https://mlmsoftware.omc.co.th/portfolio/architek-rank-improving/` |
| 62 | `https://mlmsoftware.omc.co.th/category/ข่าวขายตรง/` | 200 | `https://mlmsoftware.omc.co.th/category/ข่าวขายตรง/` |
| 63 | `https://mlmsoftware.omc.co.th/category/ประกัน/` | 200 | `https://mlmsoftware.omc.co.th/category/ประกัน/` |
| 64 | `https://mlmsoftware.omc.co.th/portfolio_category/marketing/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_category/marketing/` |
| 65 | `https://mlmsoftware.omc.co.th/portfolio_category/monitoring/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_category/monitoring/` |
| 66 | `https://mlmsoftware.omc.co.th/portfolio_category/optimization/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_category/optimization/` |
| 67 | `https://mlmsoftware.omc.co.th/portfolio_category/rank-improving/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_category/rank-improving/` |
| 68 | `https://mlmsoftware.omc.co.th/portfolio_tag/campaign/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_tag/campaign/` |
| 69 | `https://mlmsoftware.omc.co.th/portfolio_tag/cloud/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_tag/cloud/` |
| 70 | `https://mlmsoftware.omc.co.th/portfolio_tag/marketing/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_tag/marketing/` |
| 71 | `https://mlmsoftware.omc.co.th/portfolio_tag/monitor/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_tag/monitor/` |
| 72 | `https://mlmsoftware.omc.co.th/portfolio_tag/optimization/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_tag/optimization/` |
| 73 | `https://mlmsoftware.omc.co.th/portfolio_tag/ranking/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_tag/ranking/` |
| 74 | `https://mlmsoftware.omc.co.th/portfolio_tag/social/` | 200 | `https://mlmsoftware.omc.co.th/portfolio_tag/social/` |
| 75 | `https://mlmsoftware.omc.co.th/author/breeze/` | 200 | — |
| 76 | `https://mlmsoftware.omc.co.th/author/tee/` | 200 | — |
| 77 | `https://mlmsoftware.omc.co.th/mlm-software/` | 301→200 | `https://mlmsoftware.omc.co.th/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/` |
| 78 | `https://mlmsoftware.omc.co.th/mlm-system/` | 404 | — |
| 79 | `https://mlmsoftware.omc.co.th/mlmsoftware/` | 404 | — |
| 80 | `https://mlmsoftware.omc.co.th/contact-us/` | 404 | — |
| 81 | `https://mlmsoftware.omc.co.th/contact-us-2/` | 404 | — |
| 82 | `https://mlmsoftware.omc.co.th/homepage-business-solutions/` | 404 | — |
| 83 | `https://mlmsoftware.omc.co.th/pages/` | 404 | — |
| 84 | `https://mlmsoftware.omc.co.th/index.php/` | 301→200 | `https://mlmsoftware.omc.co.th/` |
| 85 | `https://mlmsoftware.omc.co.th/feed/` | 200 | — |
| 86 | `https://mlmsoftware.omc.co.th/comments/feed/` | 200 | — |
| 87 | `https://mlmsoftware.omc.co.th/blog/page/2/` | 200 | `https://mlmsoftware.omc.co.th/blog/` |
| 88 | `https://mlmsoftware.omc.co.th/blog/page/3/` | 200 | `https://mlmsoftware.omc.co.th/blog/` |
| 89 | `https://mlmsoftware.omc.co.th/category/ข่าวขายตรง/` | 200 | `https://mlmsoftware.omc.co.th/category/ข่าวขายตรง/` |
| 90 | `https://mlmsoftware.omc.co.th/category/ประกัน/` | 200 | `https://mlmsoftware.omc.co.th/category/ประกัน/` |
| 91 | `https://mlmsoftware.omc.co.th/?s=mlm` | 200 | — |
| 92 | `https://mlmsoftware.omc.co.th/wp-login.php` | 200 | — |
| 93 | `https://mlmsoftware.omc.co.th/xmlrpc.php` | 405 | — |
| 94 | `http://mlmsoftware.omc.co.th/` | 200 | `https://mlmsoftware.omc.co.th/` |
| 95 | `https://www.mlmsoftware.omc.co.th/` | 200 | `https://mlmsoftware.omc.co.th/` |

</details>
