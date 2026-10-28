from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager
import time
import random

# Setup Chrome driver
print("Setting up Chrome WebDriver...")
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

try:
    # STEP 1: Go to UIURP login page
    print("\nSTEP 1: Going to UIURP Login page")
    driver.get("http://localhost:8000/uiurp/login.php")
    print("Page Title:", driver.title)
    time.sleep(2)

    # STEP 2: Login with student credentials
    print("\nSTEP 2: Logging in with student account")
    # Find username field and enter student credentials
    username_input = driver.find_element(By.NAME, 'username')
    username_input.send_keys('01127836')  # Using a student account from the JSON file
    
    # Find password field and enter password
    password_input = driver.find_element(By.NAME, 'Password')
    password_input.send_keys('12345')
    
    # Click login button - using XPath to find the button in the login form
    login_button = driver.find_element(By.XPATH, '//div[@class="login"]/form/button')
    login_button.click()
    
    print("Login submitted")
    time.sleep(3)
    
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 3: Navigate to Forum page
    print("\nSTEP 3: Navigating to Forum")
    try:
        forum_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "forum_index.php") or contains(@href, "view_posts.php")]'))
        )
        forum_link.click()
        print("Clicked Forum link")
    except:
        print("Could not find Forum link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/view_posts.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 4: Try to create a new post (click Create Post button if available)
    print("\nSTEP 4: Trying to create a new post")
    try:
        create_post_button = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//button[contains(text(), "Create Post") or contains(@class, "create-post")]'))
        )
        create_post_button.click()
        print("Clicked Create Post button")
        
        # Wait for the modal or form to appear
        time.sleep(2)
        
        # Try to fill out the post form
        try:
            # Look for the title input field
            title_input = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.ID, 'post-title'))
            )
            title_input.send_keys("Automated Test Post")
            print("Entered post title")
            
            # Look for the content area and enter content
            try:
                # Try different possible selectors for the content area
                selectors = ['#post-content', '#content', 'textarea[name="content"]', '.ql-editor']
                content_area = None
                
                for selector in selectors:
                    try:
                        content_area = driver.find_element(By.CSS_SELECTOR, selector)
                        break
                    except NoSuchElementException:
                        continue
                
                if content_area:
                    content_area.send_keys("This is an automated test post created by Selenium.")
                    print("Entered post content")
                else:
                    print("Could not find content input field")
                
                # Try to find and select a tag if available
                try:
                    # Try to find tag checkboxes or dropdown
                    tag_elements = driver.find_elements(By.CSS_SELECTOR, 'input[type="checkbox"][name*="tag"], select[name*="tag"] option')
                    
                    if tag_elements:
                        # Select a random tag
                        random_tag = random.choice(tag_elements)
                        random_tag.click()
                        print(f"Selected a tag")
                except:
                    print("Could not find or select tags")
                
                # For demonstration only - don't actually submit to avoid creating test posts
                print("Found form elements but not submitting to avoid creating test posts")
                
                # If you want to actually submit the form, uncomment the code below
                # try:
                #     submit_button = WebDriverWait(driver, 5).until(
                #         EC.element_to_be_clickable((By.XPATH, '//button[contains(text(), "Submit") or contains(text(), "Post") or @type="submit"]'))
                #     )
                #     submit_button.click()
                #     print("Submitted the post")
                #     time.sleep(2)
                # except TimeoutException:
                #     print("Could not find submit button")
                
            except Exception as e:
                print(f"Error filling post content: {str(e)}")
                
        except TimeoutException:
            print("Could not find post form elements")
            
    except TimeoutException:
        print("Could not find Create Post button")

    # STEP 5: View a post (click on the first available post)
    print("\nSTEP 5: Viewing a post")
    try:
        # Try to find posts with different possible selectors
        post_selectors = ['.forum-post', '.post-card', '.card', 'article', '.post-item']
        post_element = None
        
        for selector in post_selectors:
            try:
                posts = WebDriverWait(driver, 5).until(
                    EC.presence_of_all_elements_located((By.CSS_SELECTOR, selector))
                )
                if posts:
                    post_element = posts[0]
                    break
            except:
                continue
                
        if post_element:
            # Try to find a clickable element within the post
            try:
                title_link = post_element.find_element(By.CSS_SELECTOR, 'a, .post-title, h2, h3')
                title_link.click()
                print("Clicked on a post")
            except:
                # If we can't find a specific element, try clicking the post itself
                post_element.click()
                print("Clicked on post container")
                
            time.sleep(3)
            print("Current URL:", driver.current_url)
            print("Page Title:", driver.title)
            
            # STEP 6: Try to add a comment if we're on a post detail page
            print("\nSTEP 6: Trying to add a comment")
            try:
                comment_input = WebDriverWait(driver, 5).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, '#comment, textarea[name*="comment"], .comment-input'))
                )
                comment_input.send_keys("This is an automated test comment.")
                print("Entered comment text")
                
                # Don't actually submit the comment to avoid creating test comments
                print("Found comment input but not submitting to avoid creating test comments")
                
                # If you want to actually submit the comment, uncomment the code below
                # try:
                #     comment_button = WebDriverWait(driver, 5).until(
                #         EC.element_to_be_clickable((By.CSS_SELECTOR, 'button[type="submit"], .comment-submit, #submit-comment'))
                #     )
                #     comment_button.click()
                #     print("Submitted comment")
                #     time.sleep(2)
                # except:
                #     print("Could not find comment submit button")
                
            except:
                print("Could not find comment input")
            
        else:
            print("Could not find any posts")
    except Exception as e:
        print(f"Error viewing posts: {str(e)}")

    # STEP 7: Navigate to different forum categories/tags if available
    print("\nSTEP 7: Checking forum categories/tags")
    try:
        # Look for category/tag links
        tag_links = driver.find_elements(By.CSS_SELECTOR, '.tag-link, .category-link, .forum-tag')
        
        if tag_links and len(tag_links) > 0:
            # Click on a random tag
            random_tag = random.choice(tag_links)
            tag_name = random_tag.text
            random_tag.click()
            print(f"Clicked on tag: {tag_name}")
            
            time.sleep(2)
            print("Current URL:", driver.current_url)
        else:
            print("No forum tags/categories found")
    except Exception as e:
        print(f"Error checking forum categories: {str(e)}")

    # STEP 8: Logout
    print("\nSTEP 8: Logging out")
    try:
        logout_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "logout.php")]'))
        )
        logout_link.click()
        print("Clicked Logout link")
    except:
        print("Could not find Logout link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/logout.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

finally:
    # Wait before closing browser
    print("\nTest completed. Waiting for 5 seconds before closing browser...")
    time.sleep(5)
    driver.quit() 