import requests
from bs4 import BeautifulSoup
import os
from urllib.parse import urljoin

# 画像を保存する関数
def save_images(url, image_save_path):
    response = requests.get(url)
    response.encoding = 'utf-8'
    soup = BeautifulSoup(response.text, 'html.parser')
    
    os.makedirs(image_save_path, exist_ok=True)
    
    # div.img_margin left 内の画像を保存
    img_div = soup.find('div', class_='img_margin left')
    if img_div:
        img_tag = img_div.find('img')
        if img_tag:
            img_url = img_tag['src']
            absolute_img_url = urljoin(url, img_url)
            img_response = requests.get(absolute_img_url)
            img_filename = os.path.join(image_save_path, f"image_1.png")
            with open(img_filename, 'wb') as img_file:
                img_file.write(img_response.content)
                print(f"画像を保存しました: {img_filename}")
    
    # span#select_a 内の画像を保存
    img_span_a = soup.find('span', id='select_a')
    if img_span_a:
        img_tag_a = img_span_a.find('img')
        if img_tag_a:
            img_url_a = img_tag_a['src']
            absolute_img_url_a = urljoin(url, img_url_a)
            img_response_a = requests.get(absolute_img_url_a)
            img_filename_a = os.path.join(image_save_path, f"image_2.png")
            with open(img_filename_a, 'wb') as img_file_a:
                img_file_a.write(img_response_a.content)
                print(f"画像を保存しました: {img_filename_a}")
    
    # span#select_i 内の画像を保存
    img_span_i = soup.find('span', id='select_i')
    if img_span_i:
        img_tag_i = img_span_i.find('img')
        if img_tag_i:
            img_url_i = img_tag_i['src']
            absolute_img_url_i = urljoin(url, img_url_i)
            img_response_i = requests.get(absolute_img_url_i)
            img_filename_i = os.path.join(image_save_path, f"image_3.png")
            with open(img_filename_i, 'wb') as img_file_i:
                img_file_i.write(img_response_i.content)
                print(f"画像を保存しました: {img_filename_i}")
    
    # span#select_u 内の画像を保存
    img_span_u = soup.find('span', id='select_u')
    if img_span_u:
        img_tag_u = img_span_u.find('img')
        if img_tag_u:
            img_url_u = img_tag_u['src']
            absolute_img_url_u = urljoin(url, img_url_u)
            img_response_u = requests.get(absolute_img_url_u)
            img_filename_u = os.path.join(image_save_path, f"image_4.png")
            with open(img_filename_u, 'wb') as img_file_u:
                img_file_u.write(img_response_u.content)
                print(f"画像を保存しました: {img_filename_u}")
    
    # span#select_e 内の画像を保存
    img_span_e = soup.find('span', id='select_e')
    if img_span_e:
        img_tag_e = img_span_e.find('img')
        if img_tag_e:
            img_url_e = img_tag_e['src']
            absolute_img_url_e = urljoin(url, img_url_e)
            img_response_e = requests.get(absolute_img_url_e)
            img_filename_e = os.path.join(image_save_path, f"image_5.png")
            with open(img_filename_e, 'wb') as img_file_e:
                img_file_e.write(img_response_e.content)
                print(f"画像を保存しました: {img_filename_e}")
    
    print("画像の保存が完了しました。")

# メインの実行部分
if __name__ == "__main__":
    url = 'https://www.fe-siken.com/kakomon/04_menjo/q3.html'
    image_save_path = r'C:\xampp\htdocs\php\卒業研究\data\image'
    
    save_images(url, image_save_path)
