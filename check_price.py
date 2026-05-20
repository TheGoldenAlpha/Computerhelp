import re
import sys
from playwright.sync_api import sync_playwright

URL = "https://mystudy.com"

def extract_price(text):
    # match patterns like $1,234 or $1,234,567 or $999
    matches = re.findall(r'\$[\d,]+', text)
    return matches

def check_price():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
            locale="de-DE",
        )
        page = context.new_page()

        try:
            page.goto(URL, wait_until="networkidle", timeout=30000)
            content = page.inner_text("body")
        except Exception as e:
            print(f"ERROR: {e}", file=sys.stderr)
            sys.exit(1)
        finally:
            browser.close()

    prices = extract_price(content)

    if prices:
        print(f"PRICE FOUND: {', '.join(prices)}")
    else:
        print("NO PRICE FOUND - page content snippet:")
        print(content[:500])

if __name__ == "__main__":
    check_price()
