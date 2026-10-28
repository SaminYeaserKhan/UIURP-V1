from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager
import time
# Set up the Chrome driver with Service object
# This automatically downloads and manages ChromeDriver
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)
# STEP 1: Go to Wikipedia
print("\nSTEP 1: Going to Wikipedia")
driver.get("https://www.wikipedia.org") # Navigate to Wikipedia
homepage
print("Page Title:", driver.title) # Verify page loaded
correctly
time.sleep(2) # Allow page to fully load
# STEP 2: Search Python Programming
print("\nSTEP 2: Search Python Programming")
# Find search input box using ID
searchbox1 = driver.find_element(By.ID, 'searchInput')
searchbox1.send_keys('Python Programming') # Enter search text
print("Entered text: Python Programming")
# Find and click search button using XPath
search_btn1 =
driver.find_element(By.XPATH,'//*[@id="search-form"]/fieldset/button'
)
search_btn1.click()
print("Clicked search button")
time.sleep(3)
print("Current URL:", driver.current_url) # Verify navigation
print("Page Title:", driver.title)
# STEP 3: Use Guido van Rossum link
print("\nSTEP 3: Click Guido van Rossum link")
# Find hyperlink using XPath
link_1 = driver.find_element(By.XPATH,
'/html/body/div[2]/div/div[3]/main/div[3]/div[3]/div[1]/p[4]/a[1]')
link_1.click()
print("Clicked Guido van Rossum link")
time.sleep(3)
print("Current URL:", driver.current_url)
print("Page Title:", driver.title)
# STEP 4: Search Artificial Intelligence XPath (Normal XPath and ID
was not working for this step. Browser kept closing.)
print("\nSTEP 4: Search Artificial Intelligence")
# Find search input using form-specific XPath
search_input = driver.find_element(By.XPATH,
'//form[@id="searchform"]//input[@name="search"]')
search_input.send_keys('Artificial Intelligence')
print("Entered text: Artificial Intelligence")
# Find search button with XPath
search_button = driver.find_element(By.XPATH,
'//form[@id="searchform"]//button[contains(@class,
"cdx-search-input__end-button")]')
search_button.click()
print("Clicked search button")
time.sleep(3)
print("Current URL:", driver.current_url)
print("Page Title:", driver.title)
# STEP 5: Use Google Assistant link
print("\nSTEP 5: Click Google Assistant link")
# Find hyperlink using XPath
google_assistant_link = driver.find_element(By.XPATH,
'/html/body/div[2]/div/div[3]/main/div[3]/div[3]/div[1]/p[3]/a[9]')
google_assistant_link.click()
print("Clicked Google Assistant link")
time.sleep(3)
print("Current URL:", driver.current_url)
print("Page Title:", driver.title)
# STEP 6: Use first font size radio button by ID
print("\nSTEP 6: Click first font size button")
font_button1 = driver.find_element(By.ID,
'skin-client-pref-vector-feature-custom-font-size-value-0')
font_button1.click()
print("Clicked first font size radio button")
time.sleep(2)
# STEP 7: Use third font size radio button by ID
print("\nSTEP 7: Click third font size button")
font_button2 = driver.find_element(By.ID,
'skin-client-pref-vector-feature-custom-font-size-value-2')
font_button2.click()
print("Clicked third font size radio button")
time.sleep(30)