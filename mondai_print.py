import requests
from bs4 import BeautifulSoup
import json

def fetch_questions(url):
    response = requests.get(url)
    response.encoding = 'utf-8'
    soup = BeautifulSoup(response.text, 'html.parser')

    mondai_elements = soup.find_all('span', id='answerChar')  # idがmondaiのdiv要素を取得

    # 取得したHTMLの一部を出力して確認
    print(soup.prettify())

    print(mondai_elements)

if __name__ == "__main__":
    url = 'https://www.fe-siken.com/kakomon/04_menjo/q2.html'
    questions = fetch_questions(url)
    print(questions)
    json_data = json.dumps({"quizzes": questions}, ensure_ascii=False, indent=4)
