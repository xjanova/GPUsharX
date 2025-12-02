<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function index()
    {
        $featuredModels = AiModel::featured()->available()->get();
        $imageModels = AiModel::byType('image')->available()->orderBy('popularity', 'desc')->get();
        $videoModels = AiModel::byType('video')->available()->orderBy('popularity', 'desc')->get();

        $categories = [
            'stable_diffusion' => 'Stable Diffusion',
            'flux' => 'FLUX',
            'animatediff' => 'AnimateDiff',
            'cogvideo' => 'CogVideo',
            'other' => 'Other',
        ];

        return view('models.index', compact('featuredModels', 'imageModels', 'videoModels', 'categories'));
    }

    public function show($modelId)
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();
        $relatedModels = AiModel::where('category', $model->category)
            ->where('id', '!=', $model->id)
            ->limit(4)
            ->get();

        return view('models.show', compact('model', 'relatedModels'));
    }

    public function installGuide($modelId)
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();

        $installSteps = $this->getInstallSteps($model);

        return view('models.install-guide', compact('model', 'installSteps'));
    }

    protected function getInstallSteps(AiModel $model): array
    {
        $steps = [];

        // Common steps
        $steps[] = [
            'title' => '1. System Requirements',
            'content' => "- GPU: NVIDIA with {$model->vram_required_mb}MB+ VRAM\n- Python: 3.10+\n- CUDA: 11.8+\n- Storage: {$model->size_mb}MB free space",
            'code' => null,
        ];

        $steps[] = [
            'title' => '2. Install Dependencies',
            'content' => 'Install the required Python packages:',
            'code' => "pip install torch torchvision --index-url https://download.pytorch.org/whl/cu118\npip install diffusers transformers accelerate safetensors",
        ];

        // Model specific
        if ($model->category === 'stable_diffusion') {
            $steps[] = [
                'title' => '3. Download Model',
                'content' => 'Download from HuggingFace:',
                'code' => "from diffusers import StableDiffusionPipeline\nimport torch\n\npipe = StableDiffusionPipeline.from_pretrained(\n    \"{$model->huggingface_id}\",\n    torch_dtype=torch.float16\n)\npipe = pipe.to(\"cuda\")",
            ];
        } elseif ($model->category === 'flux') {
            $steps[] = [
                'title' => '3. Download FLUX Model',
                'content' => 'FLUX requires additional setup:',
                'code' => "from diffusers import FluxPipeline\nimport torch\n\npipe = FluxPipeline.from_pretrained(\n    \"{$model->huggingface_id}\",\n    torch_dtype=torch.bfloat16\n)\npipe.enable_model_cpu_offload()",
            ];
        } elseif ($model->category === 'animatediff') {
            $steps[] = [
                'title' => '3. Download AnimateDiff',
                'content' => 'AnimateDiff for video generation:',
                'code' => "from diffusers import AnimateDiffPipeline, MotionAdapter\nimport torch\n\nadapter = MotionAdapter.from_pretrained(\n    \"{$model->huggingface_id}\",\n    torch_dtype=torch.float16\n)\npipe = AnimateDiffPipeline.from_pretrained(\n    \"emilianJR/epiCRealism\",\n    motion_adapter=adapter,\n    torch_dtype=torch.float16\n)\npipe = pipe.to(\"cuda\")",
            ];
        } elseif ($model->category === 'cogvideo') {
            $steps[] = [
                'title' => '3. Download CogVideoX',
                'content' => 'CogVideoX requires significant VRAM:',
                'code' => "from diffusers import CogVideoXPipeline\nimport torch\n\npipe = CogVideoXPipeline.from_pretrained(\n    \"{$model->huggingface_id}\",\n    torch_dtype=torch.bfloat16\n)\npipe.enable_model_cpu_offload()\npipe.vae.enable_tiling()",
            ];
        }

        $steps[] = [
            'title' => '4. Generate',
            'content' => 'Basic generation example:',
            'code' => $this->getGenerationCode($model),
        ];

        $steps[] = [
            'title' => '5. Integration with GPU Share',
            'content' => 'The model is automatically managed by our platform. Once installed on a node, it will receive matching jobs.',
            'code' => null,
        ];

        return $steps;
    }

    protected function getGenerationCode(AiModel $model): string
    {
        $params = $model->default_params ?? [];

        if ($model->type === 'image') {
            $width = $params['width'] ?? 1024;
            $height = $params['height'] ?? 1024;
            $steps = $params['steps'] ?? 30;

            return "prompt = \"A beautiful landscape, masterpiece, high quality\"\n\nimage = pipe(\n    prompt=prompt,\n    width={$width},\n    height={$height},\n    num_inference_steps={$steps},\n).images[0]\n\nimage.save(\"output.png\")";
        } else {
            $frames = $params['frames'] ?? 16;

            return "prompt = \"A cat walking, high quality video\"\n\nframes = pipe(\n    prompt=prompt,\n    num_frames={$frames},\n    num_inference_steps=25,\n).frames[0]\n\nexport_to_video(frames, \"output.mp4\", fps=8)";
        }
    }
}
