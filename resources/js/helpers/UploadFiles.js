import axios from 'axios'

export function getAcceptedFileTypes (fileTypes = '.pdf, .txt, .png, .jpeg, .jpg') {
  return fileTypes // update the validator in the S3 Trait if this changes
}

export async function submitUploadFile (type, form, noty, nextTick, bvModal, uploadFile, url, closeModal = true) {
  let typeFile = type + 'File'

  try {
    form.errors.clear(typeFile)
    // https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
    let formData = new FormData()
    formData.append(typeFile, form[typeFile])
    formData.append('assignmentId', form.assignmentId)
    formData.append('questionId', form.questionId)
    formData.append('type', type)
    formData.append('uploadLevel', form.uploadLevel)// at the assignment or question level; used for cutups
    formData.append('_method', 'put') // add this

    const { data } = await axios.post(url, formData)

    if (data.type === 'error') {
      form.errors.set(type, data.message)
    } else {
      noty.success(data.message)
      if (closeModal) {
        nextTick(() => {
          bvModal.hide(`modal-upload-file`)
        })
      }

      if (form.uploadLevel === 'question' && type === 'submission') {
        // immediate feedback for them to see.
        // for assignments, they'll have to click on something else to get the information

        uploadFile.date_submitted = data.date_submitted
        uploadFile.original_filename = data.original_filename
        uploadFile.date_graded = uploadFile.text_feedback = uploadFile.submission_file_score = 'N/A'
        uploadFile.file_feedback = null
        uploadFile.submission_file_exists = true
        uploadFile.submission = data.submission
        uploadFile.submission_file_url = data.submission_file_url
      }
      if (type === 'solution') {
        uploadFile.solution = data.original_filename
        uploadFile.solution_file_url = data.solution_file_url
      }
    }
  } catch (error) {
    if (error.message.includes('status code 413')) {
      return 'status code 413'
    }
    noty.error(error.message)
  }
}
