import requests
from bs4 import BeautifulSoup
import json
from github import Github, GithubException
from urllib.parse import urljoin
import os

GITHUB_TOKEN = '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'
REPO_NAME = 'suzu6331/graduate'
COMMIT_MESSAGE = 'Add scraped questions'

# 年度と試験区分の取得
def get_file_name(year, exam_type, index):
    #　秋だったら101,102,,,と保存する
    if exam_type == 'aki':
        index = 100 + index
    # それ以外は001,002,,,と保存する
    return f"{year}{str(index).zfill(3)}"

def fetch_questions(url, file_id):
    response = requests.get(url)
    response.encoding = 'utf-8'
    soup = BeautifulSoup(response.text, 'html.parser')

    questions = []
    mondai_ele = soup.find_all('div', id='mondai')  # idがmondaiのdivをmondaiにいれる
    ans_ele = soup.find_all('div', class_='ansbg')  # classがansbgのdivをansにいれる

    for i, (element, ans_element) in enumerate(zip(mondai_ele, ans_ele), 1):
        # 問題文の取得
        mondai_text = element.get_text(separator="\n", strip=True) if element else f"mondaiが見つかりません（問題{i}）"

        # 画像を含む問題文の処理
        mondai_images = element.find_all('img') if element else []
        mondai_image_tags = []
        for img_index, img_tag in enumerate(mondai_images):
            img_url = img_tag['src']
            absolute_img_url = urljoin(url, img_url)
            img_filename = f"{file_id}-{img_index}.gif"
            img_tag['src'] = img_filename
            save_image(absolute_img_url, img_filename)
            mondai_image_tags.append(f'<div class="img_margin"><img data-image-index="{img_index}"></div>')

        # 問題文に画像タグを追加する
        mondai_html = mondai_text + ''.join(mondai_image_tags)

        # 選択肢と回答
        sentaku = []
        kaitou = None
        for li_index, li in enumerate(ans_element.find_all('li')):
            img_tag = li.find('img')
            if img_tag:
                img_url = img_tag['src']
                absolute_img_url = urljoin(url, img_url)
                img_filename = f"{file_id}-{len(mondai_images) + li_index}.gif"
                img_tag['src'] = img_filename
                if len(ans_element.find_all('li')) == 1:
                    kaitou = f"<img data-image-index=\"{li_index}\">"
                else:
                    sentaku.append(f"<img data-image-index=\"{len(mondai_images) + li_index}\">")
                save_image(absolute_img_url, img_filename)
            else:
                # sentakuのアイウエを削除して選択肢の文字列だけを保存
                choice_text = li.get_text(separator="\n", strip=True)
                if choice_text[0] in "アイウエ":
                    choice_text = choice_text[1:].strip()
                sentaku.append(choice_text)

        # 正解の回答を抽出
        ans_span = soup.find('span', id='answerChar')
        if ans_span:
            ans_text = ans_span.text.strip()
            print(f"Found answer text: {ans_text}")
            ans_map = {"ア": 0, "イ": 1, "ウ": 2, "エ": 3}
            ans = ans_map.get(ans_text)
        else:
            ans = None # NULLをいれる

        # 質問保存
        question = {
            "id": file_id,
            "mondai": mondai_html
        }

        # 選択肢がある場合はsentakuを追加、ない場合はkaitouを追加
        if kaitou:
            question["kaitou"] = kaitou
        else:
            question["sentaku"] = sentaku

        # 回答保存
        question["answer"] = ans

        questions.append(question)

    return questions

# 画像を保存して、GitHubにアップ
def save_image(img_url, img_filename):
    img_response = requests.get(img_url)
    image_save_path = os.path.join('C:\\xampp\\htdocs\\php\\卒業研究\\data\\image', img_filename)
    with open(image_save_path, 'wb') as img_file:
        img_file.write(img_response.content)
        print(f"画像を保存しました: {image_save_path}")
    upload_image_to_github(img_filename, image_save_path)

# GitHubに画像をアップ
def upload_image_to_github(img_filename, image_save_path):
    g = Github(GITHUB_TOKEN)
    repo = g.get_repo(REPO_NAME)
    with open(image_save_path, 'rb') as img_file:
        content = img_file.read()
        path = f"data/image/{img_filename}"
        try:
            contents = repo.get_contents(path)
            repo.update_file(path, COMMIT_MESSAGE, content, contents.sha)
            print(f"Updated image {path} in GitHub.")
        except GithubException:
            repo.create_file(path, COMMIT_MESSAGE, content)
            print(f"Created image {path} in GitHub.")

# JSONに保存
def save_to_json(data, filename):
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)

# GitHubにディレクトリ作成
def create_github_directory(token, repo_name, directory_path, commit_message):
    g = Github(token)
    try:
        repo = g.get_repo(repo_name)
        repo.create_file(f"{directory_path}/.gitkeep", commit_message, "")
        print(f"Successfully created directory {directory_path} in GitHub.")
    except GithubException as e:
        if e.status == 422 and "already exists" in str(e.data):
            print(f"Directory {directory_path} already exists.")
        else:
            print(f"Failed to create directory {directory_path} in GitHub. Error: {e.data}")

# GitHubにアップ
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
    base_url = 'https://www.fe-siken.com/kakomon/29_aki/q'
    num_questions = 80  # 取得する問題数
    year = "2017"
    exam_type = "aki"  # または "haru" もしくは "aki"
    ##################################################################

    # ディレクトリ作成
    year_directory = f"data/{year}"
    create_github_directory(GITHUB_TOKEN, REPO_NAME, year_directory, COMMIT_MESSAGE)
    create_github_directory(GITHUB_TOKEN, REPO_NAME, f"data/image", COMMIT_MESSAGE)

    for i in range(1, num_questions + 1):
        url = f"{base_url}{i}.html"
        file_name = get_file_name(year, exam_type, i)
        questions = fetch_questions(url, file_name)
        json_data = {"quizzes": questions}
        json_file_name = f"{year_directory}/{file_name}.json"

        save_to_json(json_data, json_file_name)
        upload_to_github(GITHUB_TOKEN, REPO_NAME, json_file_name, COMMIT_MESSAGE, json.dumps(json_data, ensure_ascii=False, indent=4))

if __name__ == "__main__": main()
