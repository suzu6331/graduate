import requests
from bs4 import BeautifulSoup
import json
from github import Github, GithubException
from urllib.parse import urljoin
import os

GITHUB_TOKEN = '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'
REPO_NAME = 'suzu6331/graduate'
COMMIT_MESSAGE = 'Add scraped questions'

# 年度と問題番号を取得
def get_file_name(season, year, index):
    year_type = 0 if season == 'haru' else 1
    file_name = f"{year}{year_type}{str(index).zfill(3)}"
    return file_name

# 問題を取得
def fetch_questions(url, file_id):
    response = requests.get(url)
    response.encoding = 'utf-8'
    soup = BeautifulSoup(response.text, 'html.parser')

    questions = []
    mondai_ele = soup.find_all('div', id='mondai')  # idがmondaiのdivをmondaiにいれる
    ans_ele = soup.find_all('span', id='answerChar')  # idがanswerCharのspanをansにいれる

    for element, answer_element in zip(mondai_ele, ans_ele):
        mondai = element.text.strip() if element else "mondaiが見つかりません"

        # 画像を含む問題文
        image_tags = element.find_all('img') if element else []
        for i, img_tag in enumerate(image_tags):
            img_url = img_tag['src']
            absolute_img_url = urljoin(url, img_url)
            img_tag['src'] = f"{file_id}-{i}.png"

            # 画像を保存
            save_image(absolute_img_url, file_id, i)

        # 選択肢
        sentaku = []
        kaitou = None
        sentaku_elements = soup.find_all('div', class_='sentaku')
        for j, sentaku_element in enumerate(sentaku_elements):
            sentaku_text = sentaku_element.text.strip()
            if sentaku_element.find('img'):
                if len(sentaku_elements) == 1:
                    kaitou = f'<img data-image-index="{j}">'
                else:
                    sentaku.append(f'<img data-image-index="{j}">')
            else:
                sentaku.append(sentaku_text)

        # 回答を取得
        ans = answer_element.text.strip() if answer_element else "No ans found"

        question = {
            "id": file_id,
            "mondai": str(element),
            "sentaku": sentaku if sentaku else None,
            "kaitou": kaitou if kaitou else None,
            "ans": ans,
        }
        questions.append(question)

    return questions

# 画像を保存
def save_image(img_url, file_id, index):
    img_response = requests.get(img_url)
    img_filename = f"{file_id}-{index}.png"
    image_save_path = os.path.join('C:\\xampp\\htdocs\\php\\卒業研究\\data\\image', img_filename)
    with open(image_save_path, 'wb') as img_file:
        img_file.write(img_response.content)
        print(f"画像を保存しました: {image_save_path}")

# JSONファイルに保存する
def save_to_json(data, filename):
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)

# GitHubにディレクトリを作る
def create_github_directory(token, repo_name, directory_path, commit_message):
    g = Github(token)
    try:
        repo = g.get_repo(repo_name)
        repo.create_file(f"{directory_path}/.gitkeep", commit_message, "")
        print(f"Successfully created directory {directory_path} in GitHub.")
    except GithubException as e:
        if e.status == 422 and "already exists" in str(e):
            print(f"Directory {directory_path} already exists.")
        else:
            print(f"Failed to create directory {directory_path} in GitHub. Error: {e.data}")

# GitHubにアップロード
def upload_to_github(token, repo_name, file_path, commit_message, content):
    g = Github(token)
    try:
        repo = g.get_repo(repo_name)

        # ファイルの存在をチェックして更新するか新しく作る
        try:
            contents = repo.get_contents(file_path)
            repo.update_file(file_path, commit_message, content, contents.sha)
            print(f"Successfully updated {file_path} in GitHub.")
        except GithubException as e:
            if e.status == 404:
                repo.create_file(file_path, commit_message, content)
                print(f"Successfully created {file_path} in GitHub.")
            else:
                print(f"Failed to update {file_path} in GitHub. Error: {e.data}")
    except GithubException as e:
        print(f"Failed to upload to GitHub. Error: {e.data}")

def main():

    ##################################################################
    #任意で変えるところ
    base_url = 'https://www.fe-siken.com/kakomon/04_menjo/q'
    num_questions = 80  # 取得する問題数
    year = "2022"
    #春ならharu、秋なら秋、免除ならmenjo
    season = "menjo" 
    ##################################################################

    # ディレクトリ作成
    year_directory = f"data/{year}"
    create_github_directory(GITHUB_TOKEN, REPO_NAME, year_directory, COMMIT_MESSAGE)

    for i in range(1, num_questions + 1):
        url = f"{base_url}{i}.html"
        file_name = get_file_name(season, year, i)
        questions = fetch_questions(url, file_name)
        json_data = {"quizzes": questions}
        json_file_name = f"{year_directory}/{file_name}.json"

        save_to_json(json_data, json_file_name)
        upload_to_github(GITHUB_TOKEN, REPO_NAME, json_file_name, COMMIT_MESSAGE, json.dumps(json_data, ensure_ascii=False, indent=4))

if __name__ == "__main__":
    main()
