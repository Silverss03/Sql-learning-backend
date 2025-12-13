# API Endpoints Quick Reference

## Authentication
```
POST   /api/login                    - User login
POST   /api/register                 - User registration (Disabled)
GET    /api/user                     - Get current user (Protected)
POST   /api/logout                   - Logout user (Protected)
POST   /api/forgot-password          - Request password reset link
POST   /api/reset-password           - Reset password with token
POST   /api/change-password          - Change password (Protected)
```

## Topics
```
GET    /api/topics                   - Get all active topics
GET    /api/topics/{id}/lessons      - Get lessons for a topic
GET    /api/topics/{id}/chapter-exercises - Get chapter exercises with progress
GET    /api/topics/{id}/progress     - Get student progress for topic
```

## Lessons
```
GET    /api/lessons/{id}/questions   - Get questions for a lesson
GET    /api/lessons/{id}/exercise    - Get exercise for a lesson
POST   /api/exercise/submit          - Submit lesson exercise
```

## Student Progress
```
GET    /api/students/average-score           - Get student's average score
GET    /api/students/progress                - Get overall student progress
GET    /api/students/topics-progress         - Get progress for all topics
GET    /api/students/lesson-exercise-history - Get lesson exercise history
GET    /api/students/chapter-exercise-history - Get chapter exercise history
GET    /api/students/exam-history            - Get exam history
```

## Chapter Exercises
```
POST   /api/chapter-exercises                - Create chapter exercise (Admin)
GET    /api/chapter-exercises/{id}           - Get chapter exercise with questions
PUT    /api/chapter-exercises/{id}           - Update chapter exercise (Admin)
DELETE /api/chapter-exercises/{id}           - Delete chapter exercise (Admin)
POST   /api/chapter-exercise/submit          - Submit chapter exercise
```

## Exams
```
GET    /api/exams                    - Get future exams for student's class
GET    /api/exams/{id}               - Get exam details
POST   /api/exams/start              - Start an exam session
POST   /api/exams/submit             - Submit exam
PUT    /api/exams/{id}               - Update exam (Teacher)
DELETE /api/exams/{id}               - Delete exam (Teacher)
PUT    /api/exams/{id}/start         - Activate exam (Teacher)
```

## User
```
POST   /api/user/avatar              - Upload user avatar
```

## Questions
```
POST   /api/questions                - Create exercise with questions (Teacher)
```

## Audit
```
POST   /api/audit-logs               - Log exam violations
```

## Admin (Teacher Management)
```
POST   /api/admin/teachers           - Create teacher (Admin)
POST   /api/admin/teachers/batch     - Batch create teachers (Admin)
GET    /api/admin/teachers           - List teachers (Admin)
DELETE /api/admin/teachers/{id}      - Delete teacher (Admin)
POST   /api/admin/teachers/batch-delete - Batch delete teachers (Admin)
```

---

## Request Examples

### Get Topics
```http
GET /api/topics
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [...],
  "message": "Topics retrieved successfully",
  "success": true,
  "remark": "All active topics ordered by index"
}
```

### Submit Lesson Exercise
```http
POST /api/exercise/submit
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 1,
  "lesson_exercise_id": 5,
  "score": 85.5
}
```

**Response:**
```json
{
  "data": {
    "id": 123,
    "student_id": 1,
    "lesson_id": 5,
    "score": 85.5,
    "submitted_at": "2025-11-22T10:30:00Z"
  },
  "message": "Exercise submitted successfully",
  "success": true,
  "remark": "Submission record created"
}
```

### Start Exam
```http
POST /api/exams/start
Authorization: Bearer {token}
Content-Type: application/json

{
  "exam_id": 10,
  "device_fingerprint": "abc123xyz"
}
```

**Response:**
```json
{
  "data": {
    "session_token": "xyz789abc",
    "exam": {...},
    "questions": {
      "multipleChoice": [...],
      "sqlQuestions": [...]
    }
  },
  "message": "Exam started successfully",
  "success": true,
  "remark": "Exam session initialized for the student"
}
```

### Upload Avatar
```http
POST /api/user/avatar
Authorization: Bearer {token}
Content-Type: multipart/form-data

avatar: [file]
```

**Response:**
```json
{
  "data": {
    "avatar_url": "https://drive.google.com/..."
  },
  "message": "Avatar updated successfully",
  "success": true,
  "remark": "User avatar stored on Google Drive and URL updated"
}
```

---

## Standard Response Format

All endpoints follow this response format:

```json
{
  "data": null | object | array,
  "message": "Human-readable message",
  "success": true | false,
  "remark": "Additional context or error details"
}
```

### Success Response (200/201)
```json
{
  "data": {...},
  "message": "Operation successful",
  "success": true,
  "remark": "Additional success context"
}
```

### Validation Error (422)
```json
{
  "data": null,
  "message": "Validation failed",
  "success": false,
  "remark": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

### Not Found (404)
```json
{
  "data": null,
  "message": "Resource not found",
  "success": false,
  "remark": "Detailed explanation"
}
```

### Unauthorized (403)
```json
{
  "data": null,
  "message": "Unauthorized",
  "success": false,
  "remark": "Permission denied explanation"
}
```

### Server Error (500)
```json
{
  "data": null,
  "message": "Operation failed",
  "success": false,
  "remark": "Error message or stack trace"
}
```

---

## Controller Mapping

| Endpoint Pattern | Controller | Method |
|-----------------|------------|--------|
| `/topics*` | TopicController | index, getLessons, getChapterExercises, getProgress |
| `/lessons*` | LessonController | getQuestions, getExercise, submitExercise |
| `/students*` | StudentProgressController | getAverageScore, getOverallProgress, etc. |
| `/chapter-exercises*` | ChapterExerciseController | store, show, update, destroy, submit |
| `/exams*` | ExamController | index, show, start, submit, update, destroy |
| `/user/avatar` | UserController | uploadAvatar |
| `/user`, `/logout` | AuthController | user, logout |
| `/questions` | QuestionController | store |

---

**Last Updated**: November 22, 2025
