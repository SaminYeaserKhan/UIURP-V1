from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager
import time
import sys

# Setup Chrome driver
print("Setting up Chrome WebDriver...")
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

try:
    # Go to login page
    print("Going to login page...")
    driver.get("http://localhost:8000/uiurp/login.php")
    time.sleep(2)
    
    # Get page title
    print("Page title:", driver.title)
    
    # Print page source for debugging
    print("\nPage source (partial):")
    source = driver.page_source
    print(source[:5000] + "..." if len(source) > 5000 else source)
    
    # Try to find login form elements
    print("\nLooking for form elements...")
    
    # Find all form elements
    forms = driver.find_elements(By.TAG_NAME, "form")
    print(f"Found {len(forms)} forms")
    
    for i, form in enumerate(forms):
        print(f"\nForm #{i+1}:")
        print(f"  Action: {form.get_attribute('action')}")
        print(f"  Method: {form.get_attribute('method')}")
        
        # Find all input elements in this form
        inputs = form.find_elements(By.TAG_NAME, "input")
        print(f"  Found {len(inputs)} input elements:")
        for input_elem in inputs:
            input_type = input_elem.get_attribute("type")
            input_name = input_elem.get_attribute("name")
            input_id = input_elem.get_attribute("id")
            input_placeholder = input_elem.get_attribute("placeholder")
            print(f"    - Type: {input_type}, Name: {input_name}, ID: {input_id}, Placeholder: {input_placeholder}")
    
    # Look for specific elements
    print("\nLooking for specific elements:")
    
    # Try different strategies to find username field
    try:
        username_by_name = driver.find_elements(By.NAME, "username")
        print(f"Username fields by name 'username': {len(username_by_name)}")
    except:
        print("Error finding username by name")
    
    try:
        username_by_placeholder = driver.find_elements(By.XPATH, "//input[contains(@placeholder, 'username') or contains(@placeholder, 'User')]")
        print(f"Username fields by placeholder: {len(username_by_placeholder)}")
        for elem in username_by_placeholder:
            print(f"  Placeholder: {elem.get_attribute('placeholder')}, Name: {elem.get_attribute('name')}")
    except:
        print("Error finding username by placeholder")
    
    try:
        password_fields = driver.find_elements(By.XPATH, "//input[@type='password']")
        print(f"Password fields: {len(password_fields)}")
        for elem in password_fields:
            print(f"  Name: {elem.get_attribute('name')}, Placeholder: {elem.get_attribute('placeholder')}")
    except:
        print("Error finding password fields")
    
    try:
        buttons = driver.find_elements(By.TAG_NAME, "button")
        print(f"Buttons: {len(buttons)}")
        for elem in buttons:
            print(f"  Text: {elem.text}, Type: {elem.get_attribute('type')}")
    except:
        print("Error finding buttons")
    
finally:
    print("\nTest completed. Closing browser...")
    driver.quit()
    sys.exit(0) 